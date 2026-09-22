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
        if (!in_array($type, ['REGISTRATION', 'WELFARE'], true)) {
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

            $due = ['renewal' => 0.0, 'card' => 0.0, 'months' => array_fill(1, 12, 0.0)];
            if ($type === 'REGISTRATION') {
                if (!isset($dueByMember[$mid])) continue; // no obligation for this year
                $info = $dueByMember[$mid];
                $due['renewal'] = $info['renewal'];
                $due['card']    = $info['card'];
                foreach ($scheduleByMonth as $mo => $amt) $due['months'][$mo] = $amt;
            } else {
                if (!isset($dueByMember[$mid])) continue;
                $info = $dueByMember[$mid];
                if (($info['status'] ?? '') === 'NOT_APPLICABLE') continue;
                $start = max(1, min(12, (int)$info['start_month']));
                foreach ($scheduleByMonth as $mo => $amt) {
                    if ($mo >= $start) $due['months'][$mo] = $amt;
                }
            }

            $pay = ['renewal' => 0.0, 'card' => 0.0, 'months' => array_fill(1, 12, 0.0)];
            foreach ($paidByMember[$mid] ?? [] as $k => $v) {
                [$comp, $mo] = array_pad(explode('|', $k, 2), 2, '');
                if ($comp === 'RENEWAL') $pay['renewal'] += $v;
                elseif ($comp === 'CARD') $pay['card'] += $v;
                elseif ($comp === 'REGISTRATION' || $comp === 'WELFARE') {
                    $mI = (int)$mo;
                    if ($mI >= 1 && $mI <= 12) $pay['months'][$mI] += $v;
                }
                // ADVANCE and OTHER do not reduce monthly due — advances are spendable credit
                // and are drawn down as ADJUSTMENT rows when actually applied.
            }

            // Balance
            $bal = ['renewal' => 0.0, 'card' => 0.0, 'months' => array_fill(1, 12, 0.0)];
            $bal['renewal'] = max(0.0, $due['renewal'] - $pay['renewal']);
            $bal['card']    = max(0.0, $due['card']    - $pay['card']);
            for ($mo = 1; $mo <= 12; $mo++) {
                $bal['months'][$mo] = max(0.0, $due['months'][$mo] - $pay['months'][$mo]);
            }

            $dueTotal = $due['renewal'] + $due['card'] + array_sum($due['months']);
            $paidTotal = $pay['renewal'] + $pay['card'] + array_sum($pay['months']);
            $balTotal  = $dueTotal - $paidTotal;

            $totals['due']     += $dueTotal;
            $totals['paid']    += $paidTotal;
            $totals['balance'] += $balTotal;

            $rows[] = [
                'member'  => $m,
                'due'     => $due,
                'paid'    => $pay,
                'balance' => $bal,
                'totals'  => [
                    'due'     => round($dueTotal, 2),
                    'paid'    => round($paidTotal, 2),
                    'balance' => round($balTotal, 2),
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
}
