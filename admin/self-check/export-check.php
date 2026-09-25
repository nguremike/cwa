<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('audit.view');

$id = $_GET['id'] ?? '';

$sqlMap = [
    'active_payment_balance' => "SELECT p.id, p.receipt_no, p.amount,
                                        COALESCE(SUM(pa.amount),0) AS allocated
                                   FROM payments p
                                   LEFT JOIN payment_allocations pa ON pa.payment_id = p.id
                                  WHERE p.status = 'ACTIVE'
                                  GROUP BY p.id, p.receipt_no, p.amount
                                 HAVING ABS(COALESCE(SUM(pa.amount),0) - p.amount) > 0.005",
    'voided_has_record' => "SELECT p.id, p.receipt_no
                              FROM payments p
                              LEFT JOIN payment_voids v ON v.payment_id = p.id
                             WHERE p.status = 'VOIDED' AND v.id IS NULL",
    'voided_nets_zero'  => "SELECT p.id, p.receipt_no, COALESCE(SUM(pa.amount),0) AS net
                              FROM payments p
                              LEFT JOIN payment_allocations pa ON pa.payment_id = p.id
                             WHERE p.status = 'VOIDED'
                             GROUP BY p.id, p.receipt_no
                            HAVING ABS(COALESCE(SUM(pa.amount),0)) > 0.005",
    'advance_matches_ledger' => "SELECT ma.member_id, ma.year, ma.contribution_type, ma.amount AS bucket,
                                        COALESCE((
                                            SELECT SUM(CASE
                                                WHEN pa.component = 'ADVANCE' AND pa.amount > 0 THEN pa.amount
                                                WHEN pa.allocation_method = 'ADJUSTMENT' AND pa.amount > 0 THEN -pa.amount
                                                ELSE 0
                                            END)
                                              FROM payment_allocations pa
                                              JOIN payments p ON p.id = pa.payment_id
                                             WHERE pa.member_id = ma.member_id
                                               AND pa.year = ma.year
                                               AND pa.contribution_type = ma.contribution_type
                                               AND p.status = 'ACTIVE'
                                        ),0) AS expected
                                   FROM member_advances ma
                                  HAVING ABS(bucket - expected) > 0.005",
    'reg_no_orphans' => "SELECT mr.id, mr.member_id, mr.year
                           FROM member_registration mr
                           LEFT JOIN members m ON m.id = mr.member_id
                          WHERE m.id IS NULL",
    'wel_no_orphans' => "SELECT mw.id, mw.member_id, mw.year
                           FROM member_welfare mw
                           LEFT JOIN members m ON m.id = mw.member_id
                          WHERE m.id IS NULL",
    'open_year_has_reg_schedule' => "SELECT fy.year
                                       FROM financial_years fy
                                      WHERE fy.status = 'OPEN'
                                        AND EXISTS (
                                            SELECT 1 FROM contribution_schedules cs
                                             WHERE cs.year = fy.year AND cs.contribution_type = 'REGISTRATION'
                                        ) = 0",
    'jumuiya_no_orphans' => "SELECT j.id, j.name
                               FROM jumuiyas j
                               LEFT JOIN centers c ON c.id = j.center_id
                              WHERE c.id IS NULL",
    'member_no_orphans'  => "SELECT m.id, m.member_code, m.full_name
                               FROM members m
                               LEFT JOIN jumuiyas j ON j.id = m.jumuiya_id
                              WHERE j.id IS NULL",
    'advance_unique'     => "SELECT member_id, year, contribution_type, COUNT(*) AS c
                               FROM member_advances
                              GROUP BY member_id, year, contribution_type
                             HAVING c > 1",
    'active_payment_has_allocations' => "SELECT p.id, p.receipt_no, p.amount
                                           FROM payments p
                                           LEFT JOIN payment_allocations pa ON pa.payment_id = p.id
                                          WHERE p.status = 'ACTIVE' AND pa.id IS NULL",
    'advance_type_matches' => "SELECT pa.id, pa.payment_id, pa.contribution_type
                                 FROM payment_allocations pa
                                WHERE pa.component = 'ADVANCE'
                                  AND pa.contribution_type NOT IN ('REGISTRATION','WELFARE','OTHER')",
];

if (!isset($sqlMap[$id])) {
    http_response_code(400);
    exit('Unknown check id');
}

$rows = Db::all($sqlMap[$id]);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="self-check-' . preg_replace('/[^a-z0-9_-]/i', '', $id) . '-' . date('Ymd-His') . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

if ($rows) {
    fputcsv($out, array_keys($rows[0]));
    foreach ($rows as $r) fputcsv($out, array_values($r));
} else {
    fputcsv($out, ['OK', 'No violations']);
}
fclose($out);
exit;
