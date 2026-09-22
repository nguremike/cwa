<?php
require __DIR__ . '/../includes/auth.php';
require_permission('report.export');

$group = 'jumuiya';   // 'center' for the parish export

$f = [
    'year'       => (int)($_GET['year']      ?? 0) ?: current_year(),
    'center_id'  => (int)($_GET['center_id'] ?? 0) ?: null,
    'status'     => trim($_GET['status']     ?? ''),
];

$report = Report::aggregate($f + ['group' => $group, 'type' => 'BOTH']);

$filename = sprintf('%s-report-%d-%s.csv', $group, $report['year'], date('Ymd-His'));

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, [
    ucfirst($group),
    'Members',
    'Reg Due',
    'Reg Paid',
    'Reg Balance',
    'Wel Due',
    'Wel Paid',
    'Wel Balance',
    'Total Due',
    'Total Paid',
    'Total Balance',
]);

foreach ($report['rows'] as $r) {
    fputcsv($out, [
        $r['label'],
        $r['members'],
        number_format($r['reg_due'],     2, '.', ''),
        number_format($r['reg_paid'],    2, '.', ''),
        number_format($r['reg_balance'], 2, '.', ''),
        number_format($r['wel_due'],     2, '.', ''),
        number_format($r['wel_paid'],    2, '.', ''),
        number_format($r['wel_balance'], 2, '.', ''),
        number_format($r['due'],         2, '.', ''),
        number_format($r['paid'],        2, '.', ''),
        number_format($r['balance'],     2, '.', ''),
    ]);
}

fputcsv($out, []);
fputcsv($out, ['Generated', date('Y-m-d H:i:s')]);
fputcsv($out, ['Year',      $report['year']]);
fputcsv($out, ['Group',     $report['group']]);
fputcsv($out, ['Type',      $report['type']]);

// Totals
fputcsv($out, [
    'TOTALS',
    $report['totals']['members'],
    number_format($report['totals']['reg_due'],     2, '.', ''),
    number_format($report['totals']['reg_paid'],    2, '.', ''),
    number_format($report['totals']['reg_balance'], 2, '.', ''),
    number_format($report['totals']['wel_due'],     2, '.', ''),
    number_format($report['totals']['wel_paid'],    2, '.', ''),
    number_format($report['totals']['wel_balance'], 2, '.', ''),
    number_format($report['totals']['due'],         2, '.', ''),
    number_format($report['totals']['paid'],        2, '.', ''),
    number_format($report['totals']['balance'],     2, '.', ''),
]);

fclose($out);
exit;
