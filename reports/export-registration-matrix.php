<?php
require __DIR__ . '/../includes/auth.php';
require_permission('report.export');

$f = [
    'center_id'  => (int)($_GET['center_id']  ?? 0) ?: null,
    'jumuiya_id' => (int)($_GET['jumuiya_id'] ?? 0) ?: null,
    'year'       => (int)($_GET['year']       ?? 0) ?: current_year(),
    'status'     => trim($_GET['status']      ?? ''),
    'q'          => trim($_GET['q']           ?? ''),
];

$report = Report::memberMatrix($f + ['type' => 'REGISTRATION']);

$filename = sprintf(
    'registration-matrix-%d-%s.csv',
    $report['year'],
    date('Ymd-His')
);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

// Excel-friendly BOM
fwrite($out, "\xEF\xBB\xBF");

// Header row: identity + renewal + card + 12 months + totals
$header = [
    'Member',
    'Code',
    'Jumuiya',
    'Center',
    'Status',
    'Renewal',
    'Card',
];
for ($m = 1; $m <= 12; $m++) {
    $header[] = date('M', mktime(0, 0, 0, $m, 1));
}
$header[] = 'Total Paid';
$header[] = 'Balance';
fputcsv($out, $header);

foreach ($report['rows'] as $r) {
    $m = $r['member'];
    $line = [
        $m['full_name'],
        $m['member_code'],
        $m['jumuiya_name'],
        $m['center_name'],
        $m['status'],
        number_format($r['paid']['renewal'], 2, '.', ''),
        number_format($r['paid']['card'],    2, '.', ''),
    ];
    for ($mo = 1; $mo <= 12; $mo++) {
        $line[] = number_format($r['paid']['months'][$mo], 2, '.', '');
    }
    $line[] = number_format($r['totals']['paid'],    2, '.', '');
    $line[] = number_format($r['totals']['balance'], 2, '.', '');
    fputcsv($out, $line);
}

// Totals row
$totalsLine = ['TOTALS', '', '', '', ''];
$totalsLine[] = '';
$totalsLine[] = ''; // renewal, card
for ($mo = 1; $mo <= 12; $mo++) {
    $totalsLine[] = '';
}
$totalsLine[] = number_format($report['totals']['paid'],    2, '.', '');
$totalsLine[] = number_format($report['totals']['balance'], 2, '.', '');
fputcsv($out, $totalsLine);

// Metadata
fputcsv($out, []);
fputcsv($out, ['Generated', date('Y-m-d H:i:s')]);
fputcsv($out, ['Year', $report['year']]);
fputcsv($out, ['Type', $report['type']]);
fputcsv($out, ['Members', $report['members_count']]);

fclose($out);
exit;
