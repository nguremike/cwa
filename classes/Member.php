<?php
class Member
{
    /** Next code, atomic. e.g. CWA-000001 */
    public static function nextCode(string $prefix = 'CWA', int $pad = 6): string
    {
        // One atomic statement: seed row if missing, else increment.
        // LAST_INSERT_ID(...) records the new value so we can read it back
        // in the same connection, even inside a surrounding transaction.
        Db::q(
            "INSERT INTO member_code_seq (prefix, last_value)
             VALUES (:p, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE last_value = LAST_INSERT_ID(last_value + 1)",
            ['p' => $prefix]
        );

        $n = (int)Db::conn()->lastInsertId();
        if ($n <= 0) {
            throw new RuntimeException('Member code sequence did not advance.');
        }

        return $prefix . '-' . str_pad((string)$n, $pad, '0', STR_PAD_LEFT);
    }
    // create search all members .. all members in all jumuiyas

    public static function all(): array
    {
        return Db::all(
            "SELECT m.*, j.name AS jumuiya_name, c.name AS center_name, c.id AS center_id
               FROM members m
               JOIN jumuiyas j ON j.id = m.jumuiya_id
               JOIN centers   c ON c.id = j.center_id"

        );
    }



    /** @return array<int,array> */
    public static function search(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT m.*, j.name AS jumuiya_name, c.name AS center_name, c.id AS center_id
                  FROM members m
                  JOIN jumuiyas j ON j.id = m.jumuiya_id
                  JOIN centers   c ON c.id = j.center_id
                 WHERE 1 = 1";
        $p = [];

        if (!empty($filters['center_id'])) {
            $sql .= " AND c.id = :cid";
            $p['cid'] = (int)$filters['center_id'];
        }
        if (!empty($filters['jumuiya_id'])) {
            $sql .= " AND j.id = :jid";
            $p['jid'] = (int)$filters['jumuiya_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND m.status = :st";
            $p['st'] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            $sql .= " AND (m.full_name    LIKE :q_name
                        OR m.phone        LIKE :q_phone
                        OR m.member_code  LIKE :q_code
                        OR m.id_number    LIKE :q_idnum)";
            $like = '%' . $filters['q'] . '%';
            $p['q_name']  = $like;
            $p['q_phone'] = $like;
            $p['q_code']  = $like;
            $p['q_idnum'] = $like;
        }

        $sql .= " ORDER BY m.full_name LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        return Db::all($sql, $p);
    }

    public static function find(int $id): ?array
    {
        return Db::one(
            "SELECT m.*, j.name AS jumuiya_name, j.id AS jumuiya_id,
                    c.name AS center_name, c.id AS center_id,
                    c.welfare_enabled
               FROM members m
               JOIN jumuiyas j ON j.id = m.jumuiya_id
               JOIN centers   c ON c.id = j.center_id
              WHERE m.id = :id",
            ['id' => $id]
        );
    }

    public static function history(int $memberId): array
    {
        return Db::all(
            "SELECT h.*, jo.name AS old_name, jn.name AS new_name, u.full_name AS changed_by_name
               FROM member_jumuiya_history h
               LEFT JOIN jumuiyas jo ON jo.id = h.old_jumuiya_id
               JOIN      jumuiyas jn ON jn.id = h.new_jumuiya_id
               LEFT JOIN users    u  ON u.id  = h.changed_by
              WHERE h.member_id = :id
              ORDER BY h.effective_date DESC, h.id DESC",
            ['id' => $memberId]
        );
    }

    /**
     * Create a member + their annual registration and welfare obligations.
     * Uses the current year's contribution_schedules to compute required totals.
     */
    public static function create(array $data, int $userId): int
    {
        $year    = (int)date('Y', strtotime($data['join_date']));
        $joinMo  = (int)date('n', strtotime($data['join_date']));

        // Ensure the financial year exists
        if (!FinancialYear::byYear($year)) {
            throw new RuntimeException("Financial year {$year} does not exist. Create it first.");
        }

        Db::begin();
        try {
            $code = self::nextCode('CWA', 6);

            $memberId = Db::insert('members', [
                'member_code'   => $code,
                'full_name'     => $data['full_name'],
                'phone'         => $data['phone'],
                'id_number'     => $data['id_number']     ?? null,
                'gender'        => $data['gender']        ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'join_date'     => $data['join_date'],
                'join_month'    => $joinMo,
                'jumuiya_id'    => (int)$data['jumuiya_id'],
                'status'        => 'ACTIVE',
                'notes'         => $data['notes']         ?? null,
                'created_by'    => $userId,
                'updated_by'    => $userId,
            ]);

            // Initial jumuiya placement history
            Db::insert('member_jumuiya_history', [
                'member_id'      => $memberId,
                'old_jumuiya_id' => null,
                'new_jumuiya_id' => (int)$data['jumuiya_id'],
                'effective_date' => $data['join_date'],
                'reason'         => 'Initial registration',
                'changed_by'     => $userId,
            ]);

            // Registration obligation
            $reg = self::computeRegistrationObligation($year, $joinMo);
            Db::insert('member_registration', [
                'member_id'             => $memberId,
                'year'                  => $year,
                'renewal_required'      => $reg['renewal'],
                'registration_required' => $reg['registration'],
                'card_required'         => $reg['card'],
                'status'                => 'OPEN',
            ]);

            // Welfare obligation — only if center has welfare enabled
            $center = Db::one(
                "SELECT c.welfare_enabled
                   FROM jumuiyas j JOIN centers c ON c.id = j.center_id
                  WHERE j.id = :j",
                ['j' => (int)$data['jumuiya_id']]
            );
            if ($center && (int)$center['welfare_enabled'] === 1) {
                $wel = self::computeWelfareObligation($year, $joinMo);
                Db::insert('member_welfare', [
                    'member_id'        => $memberId,
                    'year'             => $year,
                    'welfare_required' => $wel['total'],
                    'start_month'      => $joinMo,
                    'status'           => 'OPEN',
                ]);
            } else {
                Db::insert('member_welfare', [
                    'member_id'        => $memberId,
                    'year'             => $year,
                    'welfare_required' => 0,
                    'start_month'      => $joinMo,
                    'status'           => 'NOT_APPLICABLE',
                ]);
            }

            Audit::log('CREATE', 'members', $memberId, null, [
                'member_code' => $code,
                'full_name' => $data['full_name'],
                'join_date'   => $data['join_date'],
                'year' => $year,
            ]);

            Db::commit();
            return $memberId;
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * Registration obligation for a given year and join month.
     * Rule (b): full Jan–Dec monthly schedule is the obligation,
     * regardless of join month. Renewal and card are added on top.
     */
    public static function computeRegistrationObligation(int $year, int $joinMonth): array
    {
        $renewal = (float)(Db::one(
            "SELECT setting_value FROM settings WHERE setting_key = 'renewal_amount'"
        )['setting_value'] ?? 100);
        $card = (float)(Db::one(
            "SELECT setting_value FROM settings WHERE setting_key = 'card_amount'"
        )['setting_value'] ?? 50);

        $row = Db::one(
            "SELECT COALESCE(SUM(required_amount),0) AS total
               FROM contribution_schedules
              WHERE year = :y
                AND contribution_type = 'REGISTRATION'
                AND component = 'REGISTRATION'",
            ['y' => $year]
        );
        $registration = (float)($row['total'] ?? 0);

        return [
            'renewal'      => $renewal,
            'registration' => $registration,
            'card'         => $card,
        ];
    }

    /**
     * Welfare obligation for a given year and join month.
     * Welfare starts from the join month.
     */
    public static function computeWelfareObligation(int $year, int $joinMonth): array
    {
        $row = Db::one(
            "SELECT COALESCE(SUM(required_amount),0) AS total
               FROM contribution_schedules
              WHERE year = :y
                AND contribution_type = 'WELFARE'
                AND component = 'WELFARE'
                AND month >= :m",
            ['y' => $year, 'm' => $joinMonth]
        );
        return ['total' => (float)($row['total'] ?? 0)];
    }

    public static function update(int $id, array $data, int $userId): void
    {
        $old = self::find($id);
        if (!$old) throw new RuntimeException('Member not found.');

        Db::begin();
        try {
            Db::update('members', [
                'full_name'     => $data['full_name'],
                'phone'         => $data['phone'],
                'id_number'     => $data['id_number']     ?? null,
                'gender'        => $data['gender']        ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'notes'         => $data['notes']         ?? null,
                'updated_by'    => $userId,
            ], 'id = :id', ['id' => $id]);

            Audit::log(
                'UPDATE',
                'members',
                $id,
                ['full_name' => $old['full_name'], 'phone' => $old['phone']],
                ['full_name' => $data['full_name'], 'phone' => $data['phone']]
            );

            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function transfer(int $id, int $newJumuiyaId, string $effectiveDate, string $reason, int $userId): void
    {
        $old = self::find($id);
        if (!$old) throw new RuntimeException('Member not found.');
        if ((int)$old['jumuiya_id'] === $newJumuiyaId) {
            throw new RuntimeException('Member is already in that jumuiya.');
        }

        Db::begin();
        try {
            Db::update(
                'members',
                ['jumuiya_id' => $newJumuiyaId, 'updated_by' => $userId],
                'id = :id',
                ['id' => $id]
            );

            Db::insert('member_jumuiya_history', [
                'member_id'      => $id,
                'old_jumuiya_id' => (int)$old['jumuiya_id'],
                'new_jumuiya_id' => $newJumuiyaId,
                'effective_date' => $effectiveDate,
                'reason'         => $reason,
                'changed_by'     => $userId,
            ]);

            Audit::log(
                'TRANSFER',
                'members',
                $id,
                ['jumuiya_id' => (int)$old['jumuiya_id']],
                ['jumuiya_id' => $newJumuiyaId, 'effective_date' => $effectiveDate, 'reason' => $reason]
            );

            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function changeStatus(int $id, string $newStatus, int $userId): void
    {
        $allowed = ['ACTIVE', 'INACTIVE', 'TRANSFERRED', 'DECEASED', 'LEFT', 'SUSPENDED'];
        if (!in_array($newStatus, $allowed, true)) {
            throw new RuntimeException('Invalid status.');
        }
        $old = self::find($id);
        if (!$old) throw new RuntimeException('Member not found.');

        Db::begin();
        try {
            Db::update(
                'members',
                ['status' => $newStatus, 'updated_by' => $userId],
                'id = :id',
                ['id' => $id]
            );
            Audit::log(
                'UPDATE',
                'members',
                $id,
                ['status' => $old['status']],
                ['status' => $newStatus]
            );
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }
}
