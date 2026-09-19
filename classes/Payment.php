<?php
class Payment
{
    public static function find(int $id): ?array
    {
        return Db::one(
            "SELECT p.*, m.member_code, m.full_name, m.phone,
                    j.name AS jumuiya_name, c.name AS center_name,
                    u.full_name AS entered_by_name
               FROM payments p
               JOIN members  m ON m.id = p.member_id
               JOIN jumuiyas j ON j.id = m.jumuiya_id
               JOIN centers  c ON c.id = j.center_id
               LEFT JOIN users u ON u.id = p.entered_by
              WHERE p.id = :id",
            ['id' => $id]
        );
    }

    public static function allocations(int $paymentId): array
    {
        return Db::all(
            "SELECT * FROM payment_allocations
              WHERE payment_id = :id
              ORDER BY id ASC",
            ['id' => $paymentId]
        );
    }

    /**
     * Create a payment + apply an allocation plan. Caller has already built
     * the plan (auto or manual). Runs entirely inside one transaction.
     */
    public static function createWithPlan(
        int $memberId,
        int $year,
        string $paymentDate,
        float $amount,
        string $type,
        string $method,
        ?string $reference,
        ?string $notes,
        array $plan,
        int $userId
    ): int {
        Db::begin();
        try {
            $receipt = AllocationEngine::nextReceipt($year);

            $paymentId = Db::insert('payments', [
                'receipt_no'     => $receipt,
                'member_id'      => $memberId,
                'year'           => $year,
                'payment_date'   => $paymentDate,
                'amount'         => $amount,
                'payment_type'   => $type,
                'payment_method' => $method,
                'reference_no'   => $reference,
                'notes'          => $notes,
                'status'         => 'ACTIVE',
                'entered_by'     => $userId,
            ]);

            AllocationEngine::commitPlan($paymentId, $memberId, $year, $type, $plan, $userId);

            Audit::log('PAYMENT', 'payments', $paymentId, null, [
                'receipt_no' => $receipt,
                'member_id'  => $memberId,
                'year'       => $year,
                'amount'     => $amount,
                'type'       => $type,
            ]);

            Db::commit();
            return $paymentId;
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * Void an ACTIVE payment:
     *  - mirrors every allocation with negative amount and method=REVERSAL
     *  - reverses the advance deltas: minus any ADVANCE allocation, plus any advance drawdowns
     *  - marks payment VOIDED
     *  - writes payment_voids + audit
     */
    public static function void(int $paymentId, string $reason, int $userId): void
    {
        $p = self::find($paymentId);
        if (!$p) throw new RuntimeException('Payment not found.');
        if ($p['status'] === 'VOIDED') throw new RuntimeException('Payment is already voided.');

        Db::begin();
        try {
            $allocs = self::allocations($paymentId);

            $advanceDelta = 0.0;   // net change to advance bucket

            foreach ($allocs as $a) {
                // Negative mirror
                Db::insert('payment_allocations', [
                    'payment_id'        => $paymentId,
                    'member_id'         => (int)$a['member_id'],
                    'year'              => (int)$a['year'],
                    'contribution_type' => $a['contribution_type'],
                    'component'         => $a['component'],
                    'month'             => $a['month'],
                    'amount'            => -1 * (float)$a['amount'],
                    'allocation_method' => 'REVERSAL',
                    'notes'             => 'Reversal of allocation #' . $a['id'],
                    'created_by'        => $userId,
                ]);

                // Adjust advance accounting
                if ($a['component'] === 'ADVANCE') {
                    $advanceDelta -= (float)$a['amount'];       // remove the advance we had created
                } elseif (($a['allocation_method'] ?? '') === 'ADJUSTMENT') {
                    $advanceDelta += (float)$a['amount'];       // give back the advance we had consumed
                }
            }

            if (abs($advanceDelta) > 0.0001) {
                AllocationEngine::adjustAdvance(
                    (int)$p['member_id'],
                    (int)$p['year'],
                    $p['payment_type'],
                    $advanceDelta
                );
            }

            Db::update('payments', ['status' => 'VOIDED'], 'id = :id', ['id' => $paymentId]);

            Db::insert('payment_voids', [
                'payment_id' => $paymentId,
                'reason'     => $reason,
                'voided_by'  => $userId,
            ]);

            Audit::log(
                'REVERSAL',
                'payments',
                $paymentId,
                ['status' => $p['status']],
                ['status' => 'VOIDED', 'reason' => $reason]
            );

            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function listFiltered(array $f): array
    {
        $sql = "SELECT p.*, m.member_code, m.full_name,
                       j.name AS jumuiya_name, c.name AS center_name
                  FROM payments p
                  JOIN members  m ON m.id = p.member_id
                  JOIN jumuiyas j ON j.id = m.jumuiya_id
                  JOIN centers  c ON c.id = j.center_id
                 WHERE 1 = 1";
        $p = [];
        if (!empty($f['member_id'])) {
            $sql .= " AND p.member_id = :mid";
            $p['mid'] = (int)$f['member_id'];
        }
        if (!empty($f['center_id'])) {
            $sql .= " AND c.id = :cid";
            $p['cid'] = (int)$f['center_id'];
        }
        if (!empty($f['jumuiya_id'])) {
            $sql .= " AND j.id = :jid";
            $p['jid'] = (int)$f['jumuiya_id'];
        }
        if (!empty($f['year'])) {
            $sql .= " AND p.year = :y";
            $p['y']   = (int)$f['year'];
        }
        if (!empty($f['type'])) {
            $sql .= " AND p.payment_type = :t";
            $p['t']   = $f['type'];
        }
        if (!empty($f['status'])) {
            $sql .= " AND p.status = :st";
            $p['st']  = $f['status'];
        }
        if (!empty($f['from'])) {
            $sql .= " AND p.payment_date >= :fr";
            $p['fr'] = $f['from'];
        }
        if (!empty($f['to'])) {
            $sql .= " AND p.payment_date <= :to";
            $p['to'] = $f['to'];
        }
        $sql .= " ORDER BY p.payment_date DESC, p.id DESC LIMIT 1000";
        return Db::all($sql, $p);
    }

    /**
     * @return array{ok:bool, reason:string}
     */
    public static function canCorrect(int $paymentId): array
    {
        $p = self::find($paymentId);
        if (!$p) return ['ok' => false, 'reason' => 'Payment not found.'];
        if ($p['status'] !== 'ACTIVE') return ['ok' => false, 'reason' => 'Voided payments cannot be corrected.'];

        $fy = FinancialYear::byYear((int)$p['year']);
        if (!$fy) return ['ok' => false, 'reason' => 'Financial year is missing.'];
        if ($fy['status'] !== 'OPEN') return ['ok' => false, 'reason' => 'Financial year is closed.'];

        // Must be the most recent payment for this member/year/type
        $later = Db::one(
            "SELECT id FROM payments
              WHERE member_id = :m AND year = :y AND payment_type = :t
                AND status = 'ACTIVE' AND id > :id
              LIMIT 1",
            ['m' => (int)$p['member_id'], 'y' => (int)$p['year'], 't' => $p['payment_type'], 'id' => $paymentId]
        );
        if ($later) return ['ok' => false, 'reason' => 'A later payment exists for this member, year and type. Void the newer one first.'];

        // If this payment created an advance that has since been consumed, disallow.
        $alloc = self::allocations($paymentId);
        $advanceCreated = 0.0;
        $advanceUsed    = 0.0;
        foreach ($alloc as $a) {
            if ($a['component'] === 'ADVANCE' && $a['amount'] > 0) $advanceCreated += (float)$a['amount'];
            if (($a['allocation_method'] ?? '') === 'ADJUSTMENT' && $a['amount'] > 0) $advanceUsed += (float)$a['amount'];
        }

        if ($advanceCreated > 0) {
            $bucket = (float)(Db::one(
                "SELECT amount FROM member_advances
                  WHERE member_id = :m AND year = :y AND contribution_type = :t",
                ['m' => (int)$p['member_id'], 'y' => (int)$p['year'], 't' => $p['payment_type']]
            )['amount'] ?? 0.0);

            // If the current bucket is smaller than what this payment contributed, some has been consumed.
            if ($bucket + 0.0001 < $advanceCreated) {
                return ['ok' => false, 'reason' => 'Advance created by this payment has already been partly used. Void instead.'];
            }
        }

        return ['ok' => true, 'reason' => ''];
    }
}
