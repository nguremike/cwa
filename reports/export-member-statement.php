<?php
require __DIR__ . '/../includes/auth.php';
require_permission('report.export');

$memberId = (int)($_GET['id'] ?? 0);
$year     = (int)($_GET['year'] ?? 0) ?: current_year();

try {
    $st = Report::memberStatement($memberId, $year);
} catch (Throwable $e) {
    http_response_code(404);
    exit('Statement error: ' . $e->getMessage());
}

$m   = $st['member'];
$reg = $st['registration'];
$wel = $st['wel'];
$welMeta = $st['welfare_meta'];

$filename = sprintf(
    'statement-%s-%d-%s.csv',
    $m['member_code'],
    $year,
    date('Ymd-His')
);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

// Header block
fputcsv($out, ['Catholic Women Association']);
fputcsv($out, ['Member Statement']);
fputcsv($out, ['Year', $year]);
fputcsv($out, ['Generated', date('Y-m-d H:i:s')]);
fputcsv($out, []);

fputcsv($out, ['Member', $m['full_name']]);
fputcsv($out, ['Member Code', $m['member_code']]);
fputcsv($out, ['Phone', $m['phone']]);
fputcsv($out, ['Join Date', $m['join_date']]);
fputcsv($out, ['Jumuiya', $m['jumuiya_name']]);
fputcsv($out, ['Center', $m['center_name']]);
fputcsv($out, ['Status', $m['status']]);
fputcsv($out, []);

// Registration block
fputcsv($out, ['REGISTRATION']);
fputcsv($out, ['Line', 'Due', 'Paid', 'Balance']);
if ($reg) {
    fputcsv($out, [
        'Renewal',
        number_format($reg['due']['renewal'], 2, '.', ''),
        number_format($reg['paid']['renewal'], 2, '.', ''),
        number_format($reg['balance']['renewal'], 2, '.', '')
    ]);
    if ($reg['due']['card'] > 0) {
        fputcsv($out, [
            'Card fee',
            number_format($reg['due']['card'], 2, '.', ''),
            number_format($reg['paid']['card'], 2, '.', ''),
            number_format($reg['balance']['card'], 2, '.', '')
        ]);
    }
    for ($mo = 1; $mo <= 12; $mo++) {
        $dueM = $reg['due']['months'][$mo];
        if ($dueM == 0) continue;
        fputcsv($out, [
            date('F', mktime(0, 0, 0, $mo, 1)),
            number_format($dueM, 2, '.', ''),
            number_format($reg['paid']['months'][$mo], 2, '.', ''),
            number_format($reg['balance']['months'][$mo], 2, '.', ''),
        ]);
    }
    fputcsv($out, [
        'Registration totals',
        number_format($reg['totals']['due'], 2, '.', ''),
        number_format($reg['totals']['paid'], 2, '.', ''),
        number_format($reg['totals']['balance'], 2, '.', '')
    ]);
}
fputcsv($out, []);

// Welfare block
fputcsv($out, ['WELFARE']);
if ($welMeta && $welMeta['status'] === 'NOT_APPLICABLE') {
    fputcsv($out, ['Not applicable for this member']);
} elseif ($wel) {
    fputcsv($out, ['Start Month', date('F', mktime(0, 0, 0, (int)$welMeta['start_month'], 1))]);
    fputcsv($out, ['Line', 'Due', 'Paid', 'Balance']);
    for ($mo = 1; $mo <= 12; $mo++) {
        $dueM = $wel['due']['months'][$mo];
        if ($dueM == 0) continue;
        fputcsv($out, [
            date('F', mktime(0, 0, 0, $mo, 1)),
            number_format($dueM, 2, '.', ''),
            number_format($wel['paid']['months'][$mo], 2, '.', ''),
            number_format($wel['balance']['months'][$mo], 2, '.', ''),
        ]);
    }
    fputcsv($out, [
        'Welfare totals',
        number_format($wel['totals']['due'], 2, '.', ''),
        number_format($wel['totals']['paid'], 2, '.', ''),
        number_format($wel['totals']['balance'], 2, '.', '')
    ]);
} else {
    fputcsv($out, ['No welfare obligation recorded.']);
}
fputcsv($out, []);

// Combined
fputcsv($out, ['COMBINED']);
fputcsv($out, ['Total Due',     number_format($st['totals']['due'],     2, '.', '')]);
fputcsv($out, ['Total Paid',    number_format($st['totals']['paid'],    2, '.', '')]);
fputcsv($out, ['Total Balance', number_format($st['totals']['balance'], 2, '.', '')]);

fclose($out);
exit;
