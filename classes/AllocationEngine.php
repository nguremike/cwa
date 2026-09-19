<?php
class AllocationEngine
{
    /**
     * Compute how a payment of $amount should be allocated for
     * member / year / type, honouring:
     *   - renewal-first for REGISTRATION
     *   - earliest-unpaid month first
     *   - welfare starting at member_welfare.start_month
     *   - existing advance drawn down first
     *   - overflow becomes an advance
     *
     * Returns:
     *   [
     *     'allocations' => [
     *        ['component'=>'RENEWAL','month'=>null,'amount'=>100.00,'method'=>'AUTO'],
     *        ['component'=>'REGISTRATION','month'=>1,'amount'=>30.00,'method'=>'AUTO'],
     *        ...
     *     ],
     *     'advance_used'    => float,   // existing advance consumed
     *     'advance_created' => float,   // new advance created from overflow
     *     'unallocated'     => float,   // equals advance_created; kept for clarity
     *     'summary'         => [ 'paid_before'=>..., 'due'=>..., 'paid_after'=>... ]
     *   ]
     */
    public static function plan(int $memberId, int $year, string $type, float $amount): array
    {
        $type = strtoupper($type);
        if (!in_array($type, ['REGISTRATION', 'WELFARE', 'OTHER'], true)) {
            throw new RuntimeException('Invalid payment type.');
        }
        if ($amount < 0) throw new RuntimeException('Amount must be zero or positive.');

        $member = Member::find($memberId);
        if (!$member) throw new RuntimeException('Member not found.');

        // OTHER: no scheduling. Entire amount becomes an allocation on OTHER/OTHER.
        if ($type === 'OTHER') {
            return [
                'allocations'     => $amount > 0
                    ? [['component' => 'OTHER', 'month' => null, 'amount' => $amount, 'method' => 'AUTO']]
                    : [],
                'advance_used'    => 0.0,
                'advance_created' => 0.0,
                'unallocated'     => 0.0,
                'summary'         => ['paid_before' => 0.0, 'due' => 0.0, 'paid_after' => $amount],
            ];
        }

        // 1) Outstanding structure for this member+year+type
        $structure = self::outstandingStructure($memberId, $year, $type);

        // 2) Existing advance for this member+year+type
        $existingAdvance = (float)(Db::one(
            "SELECT amount FROM member_advances
              WHERE member_id = :m AND year = :y AND contribution_type = :t",
            ['m' => $memberId, 'y' => $year, 't' => $type]
        )['amount'] ?? 0.0);

        $allocations    = [];
        $advanceUsed    = 0.0;

        // 3) Draw down the existing advance first against outstanding lines
        if ($existingAdvance > 0) {
            $remainingAdvance = $existingAdvance;
            foreach ($structure['lines'] as &$line) {
                if ($remainingAdvance <= 0) break;
                if ($line['outstanding'] <= 0) continue;
                $apply = min($remainingAdvance, $line['outstanding']);
                $line['outstanding'] -= $apply;
                $remainingAdvance    -= $apply;
                $advanceUsed         += $apply;
                $allocations[] = [
                    'component' => $line['component'],
                    'month'     => $line['month'],
                    'amount'    => round($apply, 2),
                    'method'    => 'ADJUSTMENT',
                    'notes'     => 'Advance drawdown',
                ];
            }
            unset($line);
        }

        // 4) Then apply the cash payment
        $remaining = $amount;
        foreach ($structure['lines'] as $line) {
            if ($remaining <= 0) break;
            if ($line['outstanding'] <= 0) continue;
            $apply = min($remaining, $line['outstanding']);
            $remaining -= $apply;
            $allocations[] = [
                'component' => $line['component'],
                'month'     => $line['month'],
                'amount'    => round($apply, 2),
                'method'    => 'AUTO',
            ];
        }

        // 5) Overflow becomes an advance
        $advanceCreated = $remaining > 0 ? round($remaining, 2) : 0.0;
        if ($advanceCreated > 0) {
            $allocations[] = [
                'component' => 'ADVANCE',
                'month'     => null,
                'amount'    => $advanceCreated,
                'method'    => 'AUTO',
                'notes'     => 'Overflow to advance',
            ];
        }

        return [
            'allocations'     => $allocations,
            'advance_used'    => round($advanceUsed, 2),
            'advance_created' => $advanceCreated,
            'unallocated'     => $advanceCreated,
            'summary'         => [
                'paid_before' => $structure['paid_total'],
                'due'         => $structure['due_total'],
                'paid_after'  => round($structure['paid_total'] + $advanceUsed + ($amount - $advanceCreated), 2),
            ],
        ];
    }

    /**
     * Build the ordered list of outstanding lines for a member+year+type.
     * Order: RENEWAL → CARD → REGISTRATION months (asc) → WELFARE months (asc)
     * For REGISTRATION type: RENEWAL, CARD, then REGISTRATION months 1..12.
     * For WELFARE type:      WELFARE months from start_month..12.
     */
    public static function outstandingStructure(int $memberId, int $year, string $type): array
    {
        $type = strtoupper($type);

        // Existing allocations for this member+year+type (signed sum per component+month)
        $allocRows = Db::all(
            "SELECT component, month, COALESCE(SUM(amount),0) AS paid
               FROM payment_allocations
              WHERE member_id = :m AND year = :y AND contribution_type = :t
              GROUP BY component, month",
            ['m' => $memberId, 'y' => $year, 't' => $type]
        );
        $paid = [];
        foreach ($allocRows as $r) {
            $k = $r['component'] . '|' . ($r['month'] ?? '');
            $paid[$k] = (float)$r['paid'];
        }

        $lines = [];

        if ($type === 'REGISTRATION') {
            $reg = Db::one(
                "SELECT renewal_required, registration_required, card_required
                   FROM member_registration
                  WHERE member_id = :m AND year = :y",
                ['m' => $memberId, 'y' => $year]
            );
            if (!$reg) throw new RuntimeException('No registration obligation for this year.');

            $renewal = (float)$reg['renewal_required'];
            $card    = (float)$reg['card_required'];

            if ($renewal > 0) {
                $lines[] = [
                    'component'   => 'RENEWAL',
                    'month'       => null,
                    'required'    => $renewal,
                    'paid'        => $paid['RENEWAL|'] ?? 0.0,
                    'outstanding' => max(0.0, $renewal - ($paid['RENEWAL|'] ?? 0.0)),
                ];
            }
            if ($card > 0) {
                $lines[] = [
                    'component'   => 'CARD',
                    'month'       => null,
                    'required'    => $card,
                    'paid'        => $paid['CARD|'] ?? 0.0,
                    'outstanding' => max(0.0, $card - ($paid['CARD|'] ?? 0.0)),
                ];
            }

            $sched = Db::all(
                "SELECT month, required_amount
                   FROM contribution_schedules
                  WHERE year = :y AND contribution_type = 'REGISTRATION'
                    AND component = 'REGISTRATION'
                  ORDER BY month ASC",
                ['y' => $year]
            );
            foreach ($sched as $s) {
                $m = (int)$s['month'];
                $req = (float)$s['required_amount'];
                $p   = $paid['REGISTRATION|' . $m] ?? 0.0;
                $lines[] = [
                    'component'   => 'REGISTRATION',
                    'month'       => $m,
                    'required'    => $req,
                    'paid'        => $p,
                    'outstanding' => max(0.0, $req - $p),
                ];
            }
        } elseif ($type === 'WELFARE') {
            $wel = Db::one(
                "SELECT welfare_required, start_month, status
                   FROM member_welfare
                  WHERE member_id = :m AND year = :y",
                ['m' => $memberId, 'y' => $year]
            );
            if (!$wel) throw new RuntimeException('No welfare obligation for this year.');
            if ($wel['status'] === 'NOT_APPLICABLE') {
                throw new RuntimeException('Welfare is not enabled for this member’s center.');
            }

            $start = max(1, min(12, (int)$wel['start_month']));
            $sched = Db::all(
                "SELECT month, required_amount
                   FROM contribution_schedules
                  WHERE year = :y AND contribution_type = 'WELFARE'
                    AND component = 'WELFARE'
                    AND month >= :s
                  ORDER BY month ASC",
                ['y' => $year, 's' => $start]
            );
            foreach ($sched as $s) {
                $m = (int)$s['month'];
                $req = (float)$s['required_amount'];
                $p   = $paid['WELFARE|' . $m] ?? 0.0;
                $lines[] = [
                    'component'   => 'WELFARE',
                    'month'       => $m,
                    'required'    => $req,
                    'paid'        => $p,
                    'outstanding' => max(0.0, $req - $p),
                ];
            }
        }

        $due = 0.0;
        $paidTotal = 0.0;
        foreach ($lines as $l) {
            $due += $l['required'];
            $paidTotal += $l['paid'];
        }

        return [
            'lines'     => $lines,
            'due_total' => round($due, 2),
            'paid_total' => round($paidTotal, 2),
        ];
    }

    /**
     * Persist a plan as allocations. Must be inside an outer transaction.
     *
     * @param array $plan output from self::plan()
     */
    public static function commitPlan(
        int $paymentId,
        int $memberId,
        int $year,
        string $type,
        array $plan,
        int $userId
    ): void {
        // 1) Deduct advance_used from member_advances (if any)
        if ($plan['advance_used'] > 0) {
            self::adjustAdvance($memberId, $year, $type, -1 * $plan['advance_used']);
        }

        // 2) Insert allocations
        foreach ($plan['allocations'] as $a) {
            Db::insert('payment_allocations', [
                'payment_id'        => $paymentId,
                'member_id'         => $memberId,
                'year'              => $year,
                'contribution_type' => $type,
                'component'         => $a['component'],
                'month'             => $a['month'],
                'amount'            => $a['amount'],
                'allocation_method' => $a['method'] ?? 'AUTO',
                'notes'             => $a['notes'] ?? null,
                'created_by'        => $userId,
            ]);
        }

        // 3) Add advance_created to member_advances
        if ($plan['advance_created'] > 0) {
            self::adjustAdvance($memberId, $year, $type, $plan['advance_created']);
        }

        // 4) Bump contribution_activity so schedule locks kick in for this (year, type)
        Db::q(
            "INSERT INTO contribution_activity (year, contribution_type, first_payment_at, last_payment_at, payment_count)
             VALUES (:y, :t, NOW(), NOW(), 1)
             ON DUPLICATE KEY UPDATE
                last_payment_at = NOW(),
                payment_count   = payment_count + 1",
            ['y' => $year, 't' => $type]
        );
    }

    /** Signed adjust of the advance bucket. */
    public static function adjustAdvance(int $memberId, int $year, string $type, float $delta): void
    {
        Db::q(
            "INSERT INTO member_advances (member_id, year, contribution_type, amount)
             VALUES (:m, :y, :t, :amt)
             ON DUPLICATE KEY UPDATE amount = amount + VALUES(amount)",
            ['m' => $memberId, 'y' => $year, 't' => $type, 'amt' => $delta]
        );
    }

    /**
     * Validate a manual allocation against a payment.
     * Returns [ok(bool), errors(array), unallocated(float)].
     */
    public static function validateManual(
        int $memberId,
        int $year,
        string $type,
        float $amount,
        array $manualLines
    ): array {
        $errors = [];
        $sum = 0.0;
        foreach ($manualLines as $l) {
            $a = (float)($l['amount'] ?? 0);
            if ($a < 0) {
                $errors[] = 'Negative amounts are not allowed in manual allocation.';
            }
            $sum += $a;
        }
        $sum = round($sum, 2);
        $amount = round($amount, 2);
        $unallocated = round($amount - $sum, 2);

        if ($unallocated < 0) {
            $errors[] = 'Manual allocations exceed the payment amount.';
        }
        return ['ok' => !$errors, 'errors' => $errors, 'unallocated' => $unallocated];
    }

    /** Next receipt number for a year, atomic. Must be inside outer transaction. */
    public static function nextReceipt(int $year, string $prefix = 'CWA'): string
    {
        Db::q(
            "INSERT INTO receipt_seq (year, last_value)
             VALUES (:y, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE last_value = LAST_INSERT_ID(last_value + 1)",
            ['y' => $year]
        );
        $n = (int)Db::conn()->lastInsertId();
        if ($n <= 0) throw new RuntimeException('Receipt sequence did not advance.');
        return sprintf('%s-%d-%06d', $prefix, $year, $n);
    }
}
