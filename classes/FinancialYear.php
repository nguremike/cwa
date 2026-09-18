<?php
class FinancialYear
{
    /** @return array<int,array> */
    public static function all(): array
    {
        return Db::all(
            "SELECT fy.*,
                    (SELECT COUNT(*) FROM centers) AS centers_total
               FROM financial_years fy
              ORDER BY fy.year DESC"
        );
    }

    public static function find(int $id): ?array
    {
        return Db::one("SELECT * FROM financial_years WHERE id = :id", ['id' => $id]);
    }

    public static function byYear(int $year): ?array
    {
        return Db::one("SELECT * FROM financial_years WHERE year = :y", ['y' => $year]);
    }

    public static function current(): ?array
    {
        return Db::one("SELECT * FROM financial_years WHERE is_current = 1 LIMIT 1");
    }

    public static function exists(int $year, ?int $exceptId = null): bool
    {
        $sql = "SELECT id FROM financial_years WHERE year = :y";
        $p   = ['y' => $year];
        if ($exceptId) {
            $sql .= " AND id <> :id";
            $p['id'] = $exceptId;
        }
        return (bool)Db::one($sql, $p);
    }

    public static function create(int $year, string $start, string $end, bool $makeCurrent = false): int
    {
        Db::begin();
        try {
            if ($makeCurrent) {
                Db::q("UPDATE financial_years SET is_current = 0");
            }
            $id = Db::insert('financial_years', [
                'year'       => $year,
                'start_date' => $start,
                'end_date'   => $end,
                'status'     => 'OPEN',
                'is_current' => $makeCurrent ? 1 : 0,
            ]);
            Audit::log(
                'CREATE',
                'financial_years',
                $id,
                null,
                [
                    'year' => $year,
                    'start_date' => $start,
                    'end_date' => $end,
                    'is_current' => $makeCurrent ? 1 : 0
                ]
            );
            Db::commit();
            return $id;
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function update(int $id, int $year, string $start, string $end): void
    {
        $old = self::find($id);
        if (!$old) throw new RuntimeException('Financial year not found.');
        if ($old['status'] === 'CLOSED') {
            throw new RuntimeException('Cannot edit a closed year. Reopen it first.');
        }

        Db::begin();
        try {
            Db::update('financial_years', [
                'year'       => $year,
                'start_date' => $start,
                'end_date'   => $end,
            ], 'id = :id', ['id' => $id]);
            Audit::log(
                'UPDATE',
                'financial_years',
                $id,
                ['year' => $old['year'], 'start_date' => $old['start_date'], 'end_date' => $old['end_date']],
                ['year' => $year, 'start_date' => $start, 'end_date' => $end]
            );
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function makeCurrent(int $id): void
    {
        Db::begin();
        try {
            Db::q("UPDATE financial_years SET is_current = 0");
            Db::update('financial_years', ['is_current' => 1], 'id = :id', ['id' => $id]);
            Audit::log('UPDATE', 'financial_years', $id, null, ['is_current' => 1]);
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function close(int $id): void
    {
        $y = self::find($id);
        if (!$y) throw new RuntimeException('Year not found.');
        if ($y['status'] === 'CLOSED') return;

        Db::begin();
        try {
            Db::update(
                'financial_years',
                ['status' => 'CLOSED', 'is_current' => 0],
                'id = :id',
                ['id' => $id]
            );
            Audit::log(
                'UPDATE',
                'financial_years',
                $id,
                ['status' => $y['status']],
                ['status' => 'CLOSED']
            );
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function reopen(int $id): void
    {
        $y = self::find($id);
        if (!$y) throw new RuntimeException('Year not found.');
        if ($y['status'] === 'OPEN') return;

        Db::begin();
        try {
            Db::update('financial_years', ['status' => 'OPEN'], 'id = :id', ['id' => $id]);
            Audit::log(
                'UPDATE',
                'financial_years',
                $id,
                ['status' => $y['status']],
                ['status' => 'OPEN']
            );
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * Close current year, create next year as OPEN and CURRENT,
     * copy all contribution_schedules from source -> next.
     *
     * @return array{next_year:int, copied:int}
     */
    public static function closeAndRollForward(int $sourceId): array
    {
        $src = self::find($sourceId);
        if (!$src) throw new RuntimeException('Source year not found.');
        if (self::byYear((int)$src['year'] + 1)) {
            throw new RuntimeException('Next year already exists.');
        }

        Db::begin();
        try {
            // Close source
            Db::update(
                'financial_years',
                ['status' => 'CLOSED', 'is_current' => 0],
                'id = :id',
                ['id' => $src['id']]
            );
            Db::q("UPDATE financial_years SET is_current = 0");

            // Create next
            $nextYear  = (int)$src['year'] + 1;
            $nextStart = date('Y-m-d', strtotime($src['start_date'] . ' +1 year'));
            $nextEnd   = date('Y-m-d', strtotime($src['end_date']   . ' +1 year'));
            $nextId = Db::insert('financial_years', [
                'year'       => $nextYear,
                'start_date' => $nextStart,
                'end_date'   => $nextEnd,
                'status'     => 'OPEN',
                'is_current' => 1,
            ]);

            // Copy schedules
            $rows = Db::all(
                "SELECT contribution_type, component, month, required_amount
                   FROM contribution_schedules
                  WHERE year = :y",
                ['y' => $src['year']]
            );
            $copied = 0;
            foreach ($rows as $r) {
                Db::insert('contribution_schedules', [
                    'year'              => $nextYear,
                    'contribution_type' => $r['contribution_type'],
                    'component'         => $r['component'],
                    'month'             => $r['month'],
                    'required_amount'   => $r['required_amount'],
                ]);
                $copied++;
            }

            Audit::log('UPDATE', 'financial_years', $nextId, null, [
                'rolled_from' => (int)$src['year'],
                'rolled_to'   => $nextYear,
                'schedules_copied' => $copied,
            ]);

            Db::commit();
            return ['next_year' => $nextYear, 'copied' => $copied];
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }
}
