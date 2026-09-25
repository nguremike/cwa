<?php
require __DIR__ . '/../includes/auth.php';
require_permission('payment.create');

$type = strtoupper($_GET['type'] ?? '');
if (!in_array($type, ['REGISTRATION', 'WELFARE'], true)) {
    http_response_code(400);
    exit('type must be REGISTRATION or WELFARE');
}

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    exit('Composer autoloader not found. Run: composer require phpoffice/phpspreadsheet');
}
require_once $autoload;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;

$year = current_year();

// Preload active members for the lookup sheet and named range
$members = Db::all(
    "SELECT m.id, m.member_code, m.full_name, m.phone, m.status,
            j.name AS jumuiya_name, c.name AS center_name
       FROM members m
       JOIN jumuiyas j ON j.id = m.jumuiya_id
       JOIN centers  c ON c.id = j.center_id
      WHERE m.status = 'ACTIVE'
      ORDER BY c.name, j.name, m.full_name"
);

$ss = new Spreadsheet();
$ss->getProperties()
    ->setCreator('CWA')
    ->setTitle("CWA {$type} Payment Import Template")
    ->setDescription("Fill the Data sheet and upload to /imports/upload.php");

// ---------- Instructions sheet ----------
$ins = $ss->getActiveSheet()->setTitle('Instructions');
$ins->setCellValue('A1', "CWA {$type} Payment Import — Instructions");
$ins->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$ins->mergeCells('A1:D1');

$lines = [
    '',
    'Purpose',
    "  Import multiple {$type} payments in one upload.",
    '',
    'Workflow',
    '  1. Fill the Data sheet (one payment per row).',
    '  2. Save the file, then go to Payments → Import Payments.',
    '  3. Choose the same contribution type as the file, upload, review the preview, and confirm.',
    '',
    'Required columns',
    '  Member Code    — must match an ACTIVE member in the system (see Lookups sheet).',
    '  Payment Date   — YYYY-MM-DD (e.g. 2026-09-25). Must fall in the target financial year.',
    '  Amount         — KSh, positive number, up to 2 decimals.',
    '',
    'Optional columns',
    '  Reference      — M-Pesa code, cheque number, etc.',
    '  Notes          — free text.',
    '  Method         — CASH | MPESA | BANK | CHEQUE | OTHER (defaults to CASH).',
    '',
    'Rules the system enforces',
    '  • Duplicate prevention: the same (Member Code + Date + Amount + Reference)',
    '    already imported in this batch or previously will be skipped, not re-imported.',
    '  • Allocation: every payment is allocated by the same engine that the',
    '    manual "Record Contribution" screen uses. Renewal first, then earliest',
    '    unpaid month. Any overflow becomes an advance for the member.',
    '  • Year: every row must fall within the currently OPEN financial year.',
    '',
    'Common mistakes',
    '  • Using the member name instead of the code. Codes are on the Lookups sheet.',
    '  • A date in the wrong year. The importer will reject the row.',
    '  • A blank amount. Use 0 if you mean to skip the row.',
    '  • Leading apostrophes before codes ("\'CWA-000123"). Type the code as-is.',
    '',
    'Rollback',
    '  Every upload creates one batch. If you upload the wrong file, open',
    '  Payments → Import Batches, click the batch, and use "Void batch" — that',
    '  voids every payment in the batch in one action, with a mandatory reason.',
];
$r = 2;
foreach ($lines as $line) {
    $ins->setCellValue('A' . $r, $line);
    $r++;
}
$ins->getStyle('A3')->getFont()->setBold(true);
$ins->getStyle('A6')->getFont()->setBold(true);
$ins->getStyle('A12')->getFont()->setBold(true);
$ins->getStyle('A18')->getFont()->setBold(true);
$ins->getStyle('A25')->getFont()->setBold(true);
$ins->getStyle('A31')->getFont()->setBold(true);
$ins->getStyle('A37')->getFont()->setBold(true);
$ins->getColumnDimension('A')->setWidth(120);

// ---------- Data sheet ----------
$data = $ss->createSheet()->setTitle('Data');

$headers = [
    'Member Code',       // A
    'Payment Date',      // B
    'Amount',            // C
    'Method',            // D
    'Reference',         // E
    'Notes',             // F
];
$data->fromArray($headers, null, 'A1');
$data->getStyle('A1:F1')->getFont()->setBold(true);
$data->getStyle('A1:F1')->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('F2F2F2');
$data->getStyle('A1:F1')->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Sample row (greyed) in row 2 — user deletes it before filling real data
$data->fromArray([
    'CWA-000001',
    date('Y-m-d'),
    100.00,
    'MPESA',
    'SAMPLE123',
    'Delete this sample row',
], null, 'A2');
$data->getStyle('A2:F2')->getFont()->getColor()->setARGB('999999');
$data->getStyle('A2:F2')->getFont()->setItalic(true);

$data->getColumnDimension('A')->setWidth(18);
$data->getColumnDimension('B')->setWidth(14);
$data->getColumnDimension('C')->setWidth(12);
$data->getColumnDimension('D')->setWidth(12);
$data->getColumnDimension('E')->setWidth(20);
$data->getColumnDimension('F')->setWidth(32);

// Format amount column
$data->getStyle('C3:C1000')->getNumberFormat()->setFormatCode('#,##0.00');
$data->getStyle('B3:B1000')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);

// Method data validation
$methodValidation = $data->getCell('D3')->getDataValidation();
$methodValidation->setType(DataValidation::TYPE_LIST);
$methodValidation->setErrorStyle(DataValidation::STYLE_STOP);
$methodValidation->setAllowBlank(true);
$methodValidation->setShowDropDown(true);
$methodValidation->setFormula1('"CASH,MPESA,BANK,CHEQUE,OTHER"');
// Apply to D3:D1000
for ($row = 3; $row <= 1000; $row++) {
    $data->getCell("D{$row}")->setDataValidation(clone $methodValidation);
}

// ---------- Lookups sheet (hidden) ----------
$lk = $ss->createSheet()->setTitle('Lookups');
$lk->fromArray(['Member Code', 'Full Name', 'Phone', 'Jumuiya', 'Center'], null, 'A1');
$lk->getStyle('A1:E1')->getFont()->setBold(true);
$lk->getStyle('A1:E1')->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('F2F2F2');

$row = 2;
foreach ($members as $m) {
    $lk->fromArray([
        $m['member_code'],
        $m['full_name'],
        $m['phone'],
        $m['jumuiya_name'],
        $m['center_name'],
    ], null, 'A' . $row);
    $row++;
}
$lk->getColumnDimension('A')->setWidth(16);
$lk->getColumnDimension('B')->setWidth(28);
$lk->getColumnDimension('C')->setWidth(16);
$lk->getColumnDimension('D')->setWidth(20);
$lk->getColumnDimension('E')->setWidth(20);

// Hide lookups but keep it referenced by name
$lk->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
$ss->addNamedRange(new NamedRange('MemberCodes', $lk, '$A$2:$A$' . max(2, $row - 1)));

// Apply member code dropdown to Data column A
$codeValidation = $data->getCell('A3')->getDataValidation();
$codeValidation->setType(DataValidation::TYPE_LIST);
$codeValidation->setErrorStyle(DataValidation::STYLE_STOP);
$codeValidation->setAllowBlank(false);
$codeValidation->setShowDropDown(true);
$codeValidation->setFormula1('=MemberCodes');
for ($row = 3; $row <= 1000; $row++) {
    $data->getCell("A{$row}")->setDataValidation(clone $codeValidation);
}

$ss->setActiveSheetIndexByName('Instructions');

// ---------- Stream ----------
$filename = sprintf('cwa-%s-import-template-%d.xlsx', strtolower($type), $year);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($ss);
$writer->save('php://output');
exit;
