<?php
class Report
{
    /**
     * Registration or welfare matrix for a filter set.
     *
     * @param array{
     *   center_id?: int|null,
     *   jumuiya_id?: int|null,
     *   year: int,
     *   type: string,                 // REGISTRATION | WELFARE
     *   status?: string,              // member status filter, '' = any
     *   q?: string                    // name / phone / code search
     * } $f
     * @return array{
     *   year:int, type:string,
     *   rows: array<int,array>,
     *   totals: array{due:float, paid:float, balance:float},
     *   members_count:int
     * }
     */
    public static function memberMatrix(array $f): array
    {
        $year = (int)$f['year'];
        $type = strtoupper($f['type']);
        if (!in_array($type, ['REGISTRATION', 'WELFARE', 'BOTH'], true)) {
            throw new RuntimeException('Invalid report type.');
        }

        // 1) Members matching the filter
        $sql = "SELECT m.id, m.member_code, m.full_name, m.phone, m.status, m.join_date,
                       j.name AS jumuiya_name, j.id AS jumuiya_id,
                       c.name AS center_name,  c.id AS center_id
                  FROM members m
                  JOIN jumuiyas j ON j.id = m.jumuiya_id
                  JOIN centers  c ON c.id = j.center_id
                 WHERE 1 = 1";
        $p = [];
        if (!empty($f['center_id'])) {
            $sql .= " AND c.id = :cid";
            $p['cid'] = (int)$f['center_id'];
        }
        if (!empty($f['jumuiya_id'])) {
            $sql .= " AND j.id = :jid";
            $p['jid'] = (int)$f['jumuiya_id'];
        }
        if (!empty($f['status'])) {
            $sql .= " AND m.status = :st";
            $p['st'] = $f['status'];
        }
        if (!empty($f['q'])) {
            $sql .= " AND (m.full_name LIKE :q1 OR m.phone LIKE :q2 OR m.member_code LIKE :q3)";
            $like = '%' . $f['q'] . '%';
            $p['q1'] = $like;
            $p['q2'] = $like;
            $p['q3'] = $like;
        }
        $sql .= " ORDER BY c.name, j.name, m.full_name";
        $members = Db::all($sql, $p);
        if (!$members) {
            return [
                'year' => $year,
                'type' => $type,
                'rows' => [],
                'totals' => ['due' => 0.0, 'paid' => 0.0, 'balance' => 0.0],
                'members_count' => 0,
            ];
        }

        $memberIds = array_map(fn($m) => (int)$m['id'], $members);
        $in        = implode(',', array_fill(0, count($memberIds), '?'));

        // 2) Required amounts per member (from member_registration / member_welfare)
        $dueByMember = [];
        if ($type === 'REGISTRATION') {
            $rows = Db::q(
                "SELECT member_id, renewal_required, registration_required, card_required
                   FROM member_registration
                  WHERE year = ? AND member_id IN ($in)",
                array_merge([$year], $memberIds)
            )->fetchAll();
            foreach ($rows as $r) {
                $dueByMember[(int)$r['member_id']] = [
                    'renewal' => (float)$r['renewal_required'],
                    'card'    => (float)$r['card_required'],
                    'reg_total' => (float)$r['registration_required'],
                ];
            }
            // Monthly split from schedule
            $sched = Db::all(
                "SELECT month, required_amount
                   FROM contribution_schedules
                  WHERE year = :y AND contribution_type = 'REGISTRATION'
                    AND component = 'REGISTRATION'
                  ORDER BY month",
                ['y' => $year]
            );
            $scheduleByMonth = [];
            foreach ($sched as $s) $scheduleByMonth[(int)$s['month']] = (float)$s['required_amount'];
        } else {
            $rows = Db::q(
                "SELECT member_id, welfare_required, start_month, status
                   FROM member_welfare
                  WHERE year = ? AND member_id IN ($in)",
                array_merge([$year], $memberIds)
            )->fetchAll();
            foreach ($rows as $r) {
                $dueByMember[(int)$r['member_id']] = [
                    'status'      => $r['status'],
                    'start_month' => (int)$r['start_month'],
                    'total'       => (float)$r['welfare_required'],
                ];
            }
            $sched = Db::all(
                "SELECT month, required_amount
                   FROM contribution_schedules
                  WHERE year = :y AND contribution_type = 'WELFARE'
                    AND component = 'WELFARE'
                  ORDER BY month",
                ['y' => $year]
            );
            $scheduleByMonth = [];
            foreach ($sched as $s) $scheduleByMonth[(int)$s['month']] = (float)$s['required_amount'];
        }

        // 3) Paid amounts per member from ACTIVE payments' allocations
        //    Signed sum per (member, component, month) — reversals and adjustments included.
        $paid = Db::q(
            "SELECT pa.member_id, pa.component, pa.month, COALESCE(SUM(pa.amount),0) AS paid
               FROM payment_allocations pa
               JOIN payments p ON p.id = pa.payment_id
              WHERE pa.year = ? AND pa.contribution_type = ?
                AND p.status = 'ACTIVE'
                AND pa.member_id IN ($in)
              GROUP BY pa.member_id, pa.component, pa.month",
            array_merge([$year, $type], $memberIds)
        )->fetchAll();

        $paidByMember = [];
        foreach ($paid as $r) {
            $mid = (int)$r['member_id'];
            $k   = $r['component'] . '|' . ($r['month'] ?? '');
            $paidByMember[$mid][$k] = (float)$r['paid'];
        }

        // 4) Assemble the matrix rows
        $rows = [];
        $totals = ['due' => 0.0, 'paid' => 0.0, 'balance' => 0.0];

        foreach ($members as $m) {
            $mid = (int)$m['id'];

            $due = [
                'renewal' => 0.0,
                'card'    => 0.0,
                'months'  => array_fill(1, 12, 0.0),
            ];
            $pay = [
                'renewal' => 0.0,
                'card'    => 0.0,
                'months'  => array_fill(1, 12, 0.0),
            ];

            if ($type === 'REGISTRATION') {
                if (!isset($dueByMember[$mid])) continue;
                $info = $dueByMember[$mid];
                $due['renewal'] = (float)$info['renewal'];
                $due['card']    = (float)$info['card'];
                foreach ($scheduleByMonth as $mo => $amt) {
                    $due['months'][$mo] = (float)$amt;
                }
            } else {
                if (!isset($dueByMember[$mid])) continue;
                $info = $dueByMember[$mid];
                if (($info['status'] ?? '') === 'NOT_APPLICABLE') continue;

                $start = max(1, min(12, (int)$info['start_month']));

                // Only months at or after start_month are DUE.
                // Crucially: we build $due from the SCHEDULE, not from paid.
                foreach ($scheduleByMonth as $mo => $amt) {
                    if ($mo >= $start) {
                        $due['months'][$mo] = (float)$amt;
                    }
                }

                // Sanity: if the schedule sum differs from the recorded requirement,
                // trust the recorded welfare_required but proportionally adjust the
                // last month so the total due equals the recorded obligation.
                $schedSum = array_sum($due['months']);
                $recorded = (float)$info['total'];
                if ($recorded > 0 && abs($schedSum - $recorded) > 0.01) {
                    // Adjust the last applicable month by the difference.
                    for ($mo = 12; $mo >= $start; $mo--) {
                        if ($due['months'][$mo] > 0 || $mo === $start) {
                            $due['months'][$mo] += ($recorded - $schedSum);
                            $due['months'][$mo] = max(0.0, $due['months'][$mo]);
                            break;
                        }
                    }
                }
            }

            // Paid (signed sum of allocations, reversals and adjustments included)
            foreach ($paidByMember[$mid] ?? [] as $k => $v) {
                [$comp, $mo] = array_pad(explode('|', $k, 2), 2, '');
                if ($comp === 'RENEWAL') {
                    $pay['renewal'] += (float)$v;
                } elseif ($comp === 'CARD') {
                    $pay['card'] += (float)$v;
                } elseif ($comp === 'REGISTRATION' || $comp === 'WELFARE') {
                    $mI = (int)$mo;
                    if ($mI >= 1 && $mI <= 12) {
                        $pay['months'][$mI] += (float)$v;
                    }
                }
                // ADVANCE and OTHER are intentionally ignored for month balances.
            }

            // Balance: strictly due − paid, floored at 0 per cell
            $bal = ['renewal' => 0.0, 'card' => 0.0, 'months' => array_fill(1, 12, 0.0)];
            $bal['renewal'] = max(0.0, $due['renewal'] - $pay['renewal']);
            $bal['card']    = max(0.0, $due['card']    - $pay['card']);
            for ($mo = 1; $mo <= 12; $mo++) {
                $bal['months'][$mo] = max(0.0, $due['months'][$mo] - $pay['months'][$mo]);
            }

            $dueTotal  = round($due['renewal'] + $due['card'] + array_sum($due['months']), 2);
            $paidTotal = round($pay['renewal'] + $pay['card'] + array_sum($pay['months']), 2);
            $balTotal  = round($dueTotal - $paidTotal, 2);

            $totals['due']     += $dueTotal;
            $totals['paid']    += $paidTotal;
            $totals['balance'] += $balTotal;

            $rows[] = [
                'member'  => $m,
                'due'     => $due,
                'paid'    => $pay,
                'balance' => $bal,
                'totals'  => [
                    'due'     => $dueTotal,
                    'paid'    => $paidTotal,
                    'balance' => $balTotal,
                ],
            ];
        }

        return [
            'year'          => $year,
            'type'          => $type,
            'rows'          => $rows,
            'totals'        => [
                'due'     => round($totals['due'], 2),
                'paid'    => round($totals['paid'], 2),
                'balance' => round($totals['balance'], 2),
            ],
            'members_count' => count($rows),
        ];
    }

    /**
     * Return one row per member-month where balance > 0.
     *
     * @param array{
     *   center_id?: int|null,
     *   jumuiya_id?: int|null,
     *   year: int,
     *   type: string,            // REGISTRATION | WELFARE
     *   status?: string,
     *   q?: string
     * } $f
     * @return array{
     *   year:int, type:string,
     *   rows: array<int,array>,   // each: member info + component + month + due + paid + balance
     *   totals: array{due:float, paid:float, balance:float, members:int, months:int}
     * }
     */
    public static function arrears(array $f): array
    {
        $matrix = self::memberMatrix($f);

        $rows = [];
        $totals = ['due' => 0.0, 'paid' => 0.0, 'balance' => 0.0, 'members' => 0, 'months' => 0];
        $memberIds = [];

        foreach ($matrix['rows'] as $r) {
            $m = $r['member'];
            $memberHasArrears = false;

            // Renewal and card are "month 0" in the arrears display
            if ($r['balance']['renewal'] > 0) {
                $rows[] = [
                    'member'    => $m,
                    'component' => 'RENEWAL',
                    'month'     => null,
                    'label'     => 'Renewal',
                    'due'       => $r['due']['renewal'],
                    'paid'      => $r['paid']['renewal'],
                    'balance'   => $r['balance']['renewal'],
                ];
                $totals['due']     += $r['due']['renewal'];
                $totals['paid']    += $r['paid']['renewal'];
                $totals['balance'] += $r['balance']['renewal'];
                $totals['months']  += 1;
                $memberHasArrears = true;
            }
            if ($r['balance']['card'] > 0) {
                $rows[] = [
                    'member'    => $m,
                    'component' => 'CARD',
                    'month'     => null,
                    'label'     => 'Card fee',
                    'due'       => $r['due']['card'],
                    'paid'      => $r['paid']['card'],
                    'balance'   => $r['balance']['card'],
                ];
                $totals['due']     += $r['due']['card'];
                $totals['paid']    += $r['paid']['card'];
                $totals['balance'] += $r['balance']['card'];
                $totals['months']  += 1;
                $memberHasArrears = true;
            }
            for ($mo = 1; $mo <= 12; $mo++) {
                if ($r['balance']['months'][$mo] <= 0) continue;
                $comp = $matrix['type'] === 'WELFARE' ? 'WELFARE' : 'REGISTRATION';
                $rows[] = [
                    'member'    => $m,
                    'component' => $comp,
                    'month'     => $mo,
                    'label'     => date('M', mktime(0, 0, 0, $mo, 1)),
                    'due'       => $r['due']['months'][$mo],
                    'paid'      => $r['paid']['months'][$mo],
                    'balance'   => $r['balance']['months'][$mo],
                ];
                $totals['due']     += $r['due']['months'][$mo];
                $totals['paid']    += $r['paid']['months'][$mo];
                $totals['balance'] += $r['balance']['months'][$mo];
                $totals['months']  += 1;
                $memberHasArrears = true;
            }

            if ($memberHasArrears) {
                $memberIds[(int)$m['id']] = true;
            }
        }

        $totals['members'] = count($memberIds);

        // Stable sort: center, jumuiya, member name, then month order (renewal/card first)
        usort($rows, function ($a, $b) {
            $c = strcmp($a['member']['center_name'],  $b['member']['center_name']);
            if ($c) return $c;
            $c = strcmp($a['member']['jumuiya_name'], $b['member']['jumuiya_name']);
            if ($c) return $c;
            $c = strcmp($a['member']['full_name'],    $b['member']['full_name']);
            if ($c) return $c;
            $ao = $a['month'] ?? 0;
            $bo = $b['month'] ?? 0;
            return $ao <=> $bo;
        });

        return [
            'year'   => $matrix['year'],
            'type'   => $matrix['type'],
            'rows'   => $rows,
            'totals' => [
                'due'     => round($totals['due'], 2),
                'paid'    => round($totals['paid'], 2),
                'balance' => round($totals['balance'], 2),
                'members' => $totals['members'],
                'months'  => $totals['months'],
            ],
        ];
    }

    /**
     * Balance matrix — same shape as memberMatrix(), but each month cell
     * is the OUTSTANDING amount (0 if settled, · if not due).
     *
     * @param array $f same filter shape as memberMatrix(), plus type
     * @return array{year:int, type:string, rows:array, totals:array, members_count:int}
     */
    public static function balanceMatrix(array $f): array
    {
        $m = self::memberMatrix($f);
        $rows = [];

        $totals = ['due' => 0.0, 'paid' => 0.0, 'balance' => 0.0];

        foreach ($m['rows'] as $r) {
            $balOnly = $r['balance'];
            $dueOnly = $r['due'];
            $paidOnly = $r['paid'];

            // Skip members with zero balance entirely — the arrears list
            // should only contain those who owe something.
            if (($r['totals']['balance'] ?? 0) <= 0) continue;

            $rows[] = $r;

            $totals['due']     += $r['totals']['due'];
            $totals['paid']    += $r['totals']['paid'];
            $totals['balance'] += $r['totals']['balance'];
        }

        return [
            'year'          => $m['year'],
            'type'          => $m['type'],
            'rows'          => $rows,
            'totals'        => [
                'due'     => round($totals['due'], 2),
                'paid'    => round($totals['paid'], 2),
                'balance' => round($totals['balance'], 2),
            ],
            'members_count' => count($rows),
        ];
    }

    /**
     * Full statement for one member in one year.
     *
     * @return array{
     *   member: array,
     *   year: int,
     *   registration: array|null,   // memberMatrix row for type=REGISTRATION
     *   welfare: array|null,        // memberMatrix row for type=WELFARE
     *   totals: array{
     *     due: float, paid: float, balance: float
     *   }
     * }
     */
    public static function memberStatement(int $memberId, int $year): array
    {
        $m = Member::find($memberId);
        if (!$m) throw new RuntimeException('Member not found.');

        $reg = self::memberMatrix([
            'year' => $year,
            'type' => 'REGISTRATION',
            'q' => $m['member_code'],
        ]);
        $wel = self::memberMatrix([
            'year' => $year,
            'type' => 'WELFARE',
            'q' => $m['member_code'],
        ]);

        // memberMatrix returns rows for every matching member;
        // we only keep the one whose id equals $memberId
        $regRow = null;
        foreach ($reg['rows'] as $r) {
            if ((int)$r['member']['id'] === $memberId) {
                $regRow = $r;
                break;
            }
        }
        $welRow = null;
        foreach ($wel['rows'] as $r) {
            if ((int)$r['member']['id'] === $memberId) {
                $welRow = $r;
                break;
            }
        }

        $totals = [
            'due'     => (float)($regRow['totals']['due']     ?? 0) + (float)($welRow['totals']['due']     ?? 0),
            'paid'    => (float)($regRow['totals']['paid']    ?? 0) + (float)($welRow['totals']['paid']    ?? 0),
            'balance' => (float)($regRow['totals']['balance'] ?? 0) + (float)($welRow['totals']['balance'] ?? 0),
        ];

        // Welfare status for the block
        $welStatus = Db::one(
            "SELECT status, start_month, welfare_required
               FROM member_welfare
              WHERE member_id = :m AND year = :y",
            ['m' => $memberId, 'y' => $year]
        );

        return [
            'member'       => $m,
            'year'         => $year,
            'registration' => $regRow,
            'welfare'      => $welRow,
            'welfare_meta' => $welStatus,
            'totals'       => [
                'due'     => round($totals['due'], 2),
                'paid'    => round($totals['paid'], 2),
                'balance' => round($totals['balance'], 2),
            ],
        ];
    }

    /**
     * Aggregate the matrix by a grouping level.
     *
     * @param array $f   Same filter set as memberMatrix(), plus:
     *                     - 'group' => 'jumuiya' | 'center'
     *                     - 'type'  => 'REGISTRATION' | 'WELFARE' | 'BOTH'
     * @return array{
     *   year:int, group:string, type:string,
     *   rows: array<int,array>,           // one per group bucket
     *   totals: array{
     *     members:int, due:float, paid:float, balance:float,
     *     reg_due:float, reg_paid:float, reg_balance:float,
     *     wel_due:float, wel_paid:float, wel_balance:float
     *   }
     * }
     */
    /**
     * Aggregate the matrix by a grouping level.
     *
     * @param array $f   Same filter set as memberMatrix(), plus:
     *                     - 'group' => 'jumuiya' | 'center'
     *                     - 'type'  => 'REGISTRATION' | 'WELFARE' | 'BOTH'
     * @return array{year:int, group:string, type:string, rows:array, totals:array}
     */
    public static function aggregate(array $f): array
    {
        $year = (int)($f['year'] ?? 0);
        if ($year <= 0) {
            throw new RuntimeException('Aggregate requires an explicit year.');
        }

        $group = strtolower($f['group'] ?? 'jumuiya');
        if (!in_array($group, ['jumuiya', 'center'], true)) {
            throw new RuntimeException('Invalid grouping.');
        }

        $typeParam = strtoupper($f['type'] ?? 'BOTH');
        if ($typeParam === 'BOTH') {
            $types = ['REGISTRATION', 'WELFARE'];
        } elseif (in_array($typeParam, ['REGISTRATION', 'WELFARE'], true)) {
            $types = [$typeParam];
        } else {
            throw new RuntimeException('Invalid type.');
        }

        // IMPORTANT: rebuild the filter explicitly for each matrix call.
        // Never pass the outer $f directly — it carries a 'type' key that
        // collides with the matrix's required type, and PHP's + operator
        // preserves the left-hand key, so the wrong type would be used.
        $baseFilter = [
            'year'       => $year,
            'center_id'  => !empty($f['center_id'])  ? (int)$f['center_id']  : null,
            'jumuiya_id' => !empty($f['jumuiya_id']) ? (int)$f['jumuiya_id'] : null,
            'status'     => trim((string)($f['status'] ?? '')),
            'q'          => trim((string)($f['q'] ?? '')),
        ];

        $matrices = [];
        foreach ($types as $t) {
            $matrixFilter = $baseFilter;
            $matrixFilter['type'] = $t;   // explicit — no merge ambiguity
            $matrices[$t] = self::memberMatrix($matrixFilter);

            if ((int)$matrices[$t]['year'] !== $year) {
                throw new RuntimeException(
                    "Aggregate year mismatch: asked for {$year}, matrix returned "
                        . (int)$matrices[$t]['year']
                );
            }
        }

        // Build bucket accumulation
        $buckets = [];

        foreach ($types as $t) {
            foreach ($matrices[$t]['rows'] as $r) {
                $m = $r['member'];
                $key = $group === 'jumuiya'
                    ? (string)$m['jumuiya_name'] . "\x1F" . $m['center_name']
                    : (string)$m['center_name'];

                if (!isset($buckets[$key])) {
                    $buckets[$key] = [
                        'id'          => $group === 'jumuiya'
                            ? (int)$m['jumuiya_id']
                            : (int)$m['center_id'],
                        'label'       => $group === 'jumuiya'
                            ? $m['jumuiya_name']
                            : $m['center_name'],
                        'sublabel'    => $group === 'jumuiya'
                            ? $m['center_name']
                            : '',
                        'members'     => [],
                        'reg_due'     => 0.0,
                        'reg_paid'    => 0.0,
                        'reg_balance' => 0.0,
                        'wel_due'     => 0.0,
                        'wel_paid'    => 0.0,
                        'wel_balance' => 0.0,
                    ];
                }
                $buckets[$key]['members'][(int)$m['id']] = true;

                // Read the flattened totals — same key path memberMatrix uses.
                $rowDue     = (float)($r['totals']['due']     ?? 0);
                $rowPaid    = (float)($r['totals']['paid']    ?? 0);
                $rowBalance = (float)($r['totals']['balance'] ?? 0);

                if ($t === 'REGISTRATION') {
                    $buckets[$key]['reg_due']     += $rowDue;
                    $buckets[$key]['reg_paid']    += $rowPaid;
                    $buckets[$key]['reg_balance'] += $rowBalance;
                } else {
                    $buckets[$key]['wel_due']     += $rowDue;
                    $buckets[$key]['wel_paid']    += $rowPaid;
                    $buckets[$key]['wel_balance'] += $rowBalance;
                }
            }
        }

        // Finalize
        $rows = [];
        $totals = [
            'members' => 0,
            'due' => 0.0,
            'paid' => 0.0,
            'balance' => 0.0,
            'reg_due' => 0.0,
            'reg_paid' => 0.0,
            'reg_balance' => 0.0,
            'wel_due' => 0.0,
            'wel_paid' => 0.0,
            'wel_balance' => 0.0,
        ];

        foreach ($buckets as $b) {
            $memberCount = count($b['members']);
            $due     = $b['reg_due']     + $b['wel_due'];
            $paid    = $b['reg_paid']    + $b['wel_paid'];
            $balance = $b['reg_balance'] + $b['wel_balance'];

            $rows[] = [
                'id'          => (int)$b['id'],
                'label'       => $b['label'],
                'sublabel'    => $b['sublabel'],
                'members'     => $memberCount,
                'reg_due'     => round($b['reg_due'],     2),
                'reg_paid'    => round($b['reg_paid'],    2),
                'reg_balance' => round($b['reg_balance'], 2),
                'wel_due'     => round($b['wel_due'],     2),
                'wel_paid'    => round($b['wel_paid'],    2),
                'wel_balance' => round($b['wel_balance'], 2),
                'due'         => round($due,     2),
                'paid'        => round($paid,    2),
                'balance'     => round($balance, 2),
            ];

            $totals['members']     += $memberCount;
            $totals['due']         += $due;
            $totals['paid']        += $paid;
            $totals['balance']     += $balance;
            $totals['reg_due']     += $b['reg_due'];
            $totals['reg_paid']    += $b['reg_paid'];
            $totals['reg_balance'] += $b['reg_balance'];
            $totals['wel_due']     += $b['wel_due'];
            $totals['wel_paid']    += $b['wel_paid'];
            $totals['wel_balance'] += $b['wel_balance'];
        }

        usort($rows, fn($a, $b) => strcmp($a['label'], $b['label']));

        foreach (['due', 'paid', 'balance', 'reg_due', 'reg_paid', 'reg_balance', 'wel_due', 'wel_paid', 'wel_balance'] as $k) {
            $totals[$k] = round($totals[$k], 2);
        }

        return [
            'year'   => $year,
            'group'  => $group,
            'type'   => $typeParam,
            'rows'   => $rows,
            'totals' => $totals,
        ];
    }

    /**
     * Everything the dashboard needs for one year, in one call.
     *
     * @return array{
     *   year:int,
     *   counts: array{
     *     members:int, active_members:int, new_members:int,
     *     centers:int, jumuiyas:int, users:int
     *   },
     *   registration: array{due:float, paid:float, balance:float, pct:float},
     *   welfare:      array{due:float, paid:float, balance:float, pct:float},
     *   arrears:      array{
     *     reg_members:int, wel_members:int, total_balance:float
     *   },
     *   monthly: array<int, array{reg:float, wel:float}>,   // 1..12
     *   jumuiya_comparison: array<int, array{label:string, due:float, paid:float}>,
     *   collection_status: array{fully:int, partial:int, arrears:int, not_started:int},
     *   members_by_center: array<int, array{label:string, count:int}>
     * }
     */
    public static function dashboard(int $year): array
    {
        // ---- counts ----
        $counts = [
            'members'        => (int)(Db::one("SELECT COUNT(*) c FROM members")['c'] ?? 0),
            'active_members' => (int)(Db::one("SELECT COUNT(*) c FROM members WHERE status='ACTIVE'")['c'] ?? 0),
            'new_members'    => (int)(Db::one(
                "SELECT COUNT(*) c FROM members WHERE YEAR(join_date) = :y",
                ['y' => $year]
            )['c'] ?? 0),
            'centers'        => (int)(Db::one("SELECT COUNT(*) c FROM centers WHERE status='ACTIVE'")['c'] ?? 0),
            'jumuiyas'       => (int)(Db::one("SELECT COUNT(*) c FROM jumuiyas WHERE status='ACTIVE'")['c'] ?? 0),
            'users'          => (int)(Db::one("SELECT COUNT(*) c FROM users WHERE status='ACTIVE'")['c'] ?? 0),
        ];

        // ---- registration + welfare via matrix (already trusted) ----
        $regMatrix = self::memberMatrix(['year' => $year, 'type' => 'REGISTRATION']);
        $welMatrix = self::memberMatrix(['year' => $year, 'type' => 'WELFARE']);

        $reg = self::totalsWithPct($regMatrix['totals']);
        $wel = self::totalsWithPct($welMatrix['totals']);

        // ---- arrears ----
        $regArr = self::balanceMatrix(['year' => $year, 'type' => 'REGISTRATION']);
        $welArr = self::balanceMatrix(['year' => $year, 'type' => 'WELFARE']);

        $arrears = [
            'reg_members'   => (int)$regArr['members_count'],
            'wel_members'   => (int)$welArr['members_count'],
            'total_balance' => round($regArr['totals']['balance'] + $welArr['totals']['balance'], 2),
        ];

        // ---- monthly collections (from payment_allocations, ACTIVE payments only) ----
        $monthly = array_fill(1, 12, ['reg' => 0.0, 'wel' => 0.0]);
        $rows = Db::all(
            "SELECT pa.contribution_type, pa.month, COALESCE(SUM(pa.amount),0) AS paid
               FROM payment_allocations pa
               JOIN payments p ON p.id = pa.payment_id
              WHERE pa.year = :y
                AND p.status = 'ACTIVE'
                AND pa.component IN ('REGISTRATION','WELFARE')
                AND pa.month IS NOT NULL
              GROUP BY pa.contribution_type, pa.month",
            ['y' => $year]
        );
        foreach ($rows as $r) {
            $m = (int)$r['month'];
            if ($m < 1 || $m > 12) continue;
            if ($r['contribution_type'] === 'REGISTRATION') $monthly[$m]['reg'] += (float)$r['paid'];
            if ($r['contribution_type'] === 'WELFARE')      $monthly[$m]['wel'] += (float)$r['paid'];
        }
        // Round
        for ($m = 1; $m <= 12; $m++) {
            $monthly[$m]['reg'] = round($monthly[$m]['reg'], 2);
            $monthly[$m]['wel'] = round($monthly[$m]['wel'], 2);
        }

        // ---- jumuiya comparison ----
        $aggJ = self::aggregate([
            'year'  => $year,
            'group' => 'jumuiya',
            'type'  => 'BOTH',
        ]);
        $jumuiyaComparison = [];
        foreach ($aggJ['rows'] as $r) {
            $jumuiyaComparison[] = [
                'label' => $r['label'],
                'due'   => (float)$r['due'],
                'paid'  => (float)$r['paid'],
            ];
        }
        // Sort by paid desc, cap to top 15 for the chart
        usort($jumuiyaComparison, fn($a, $b) => $b['paid'] <=> $a['paid']);
        $jumuiyaComparison = array_slice($jumuiyaComparison, 0, 15);

        // ---- collection status (per member, using registration as the driver) ----
        $status = ['fully' => 0, 'partial' => 0, 'arrears' => 0, 'not_started' => 0];
        foreach ($regMatrix['rows'] as $r) {
            $due     = (float)$r['totals']['due'];
            $paid    = (float)$r['totals']['paid'];
            $balance = (float)$r['totals']['balance'];

            if ($due <= 0) {
                $status['not_started']++;
            } elseif ($balance <= 0) {
                $status['fully']++;
            } elseif ($paid > 0) {
                $status['partial']++;
            } else {
                $status['arrears']++;
            }
        }

        // ---- members by center ----
        $mbc = Db::all(
            "SELECT c.name AS label, COUNT(m.id) AS cnt
               FROM centers c
               LEFT JOIN jumuiyas j ON j.center_id = c.id
               LEFT JOIN members   m ON m.jumuiya_id = j.id AND m.status = 'ACTIVE'
              WHERE c.status = 'ACTIVE'
              GROUP BY c.id, c.name
              ORDER BY cnt DESC, c.name"
        );
        $membersByCenter = [];
        foreach ($mbc as $r) {
            $membersByCenter[] = [
                'label' => $r['label'],
                'count' => (int)$r['cnt'],
            ];
        }

        return [
            'year'                => $year,
            'counts'              => $counts,
            'registration'        => $reg,
            'welfare'             => $wel,
            'arrears'             => $arrears,
            'monthly'             => $monthly,
            'jumuiya_comparison'  => $jumuiyaComparison,
            'collection_status'   => $status,
            'members_by_center'   => $membersByCenter,
        ];
    }

    /** Attach a percentage to a totals array. */
    private static function totalsWithPct(array $t): array
    {
        $due  = (float)($t['due'] ?? 0);
        $paid = (float)($t['paid'] ?? 0);
        $bal  = (float)($t['balance'] ?? 0);
        $pct  = $due > 0 ? round(($paid / $due) * 100, 1) : 0.0;
        return ['due' => $due, 'paid' => $paid, 'balance' => $bal, 'pct' => $pct];
    }
}
