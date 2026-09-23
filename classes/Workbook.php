<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class Workbook
{
    /**
     * Build the workbook and stream it to the browser.
     *
     * @param array{
     *   year: int,
     *   center_id: ?int,
     *   sheets: string[]      // subset of: dashboard,reg_matrix,wel_matrix,reg_arrears,wel_arrears,center_report,parish_report,payments,members
     * } $options
     */
    public static function stream(array $options): void
    {
        $year     = (int)$options['year'];
        $centerId = $options['center_id'] ?? null;
        $sheets   = $options['sheets'] ?? [];

        $ss = new Spreadsheet();
        $ss->getProperties()
            ->setCreator('CWA')
            ->setTitle("CWA Month-End Pack {$year}")
            ->setDescription("Generated " . date('Y-m-d H:i'));

        // Remove the default empty sheet; we will add our own.
        $ss->removeSheetByIndex(0);

        $usedNames = [];
        $addSheet = function (string $title) use ($ss, &$usedNames) {
            $base = $title;
            $i = 1;
            while (in_array($title, $usedNames, true)) {
                $title = mb_substr($base, 0, 25) . '_' . (++$i);
            }
            $usedNames[] = $title;
            return $ss->createSheet()->setTitle($title);
        };

        if (in_array('dashboard', $sheets, true)) {
            self::sheetDashboard($addSheet('Dashboard'), $year);
        }
        if (in_array('reg_matrix', $sheets, true)) {
            self::sheetMatrix($addSheet('Registration'), $year, 'REGISTRATION', $centerId, false);
        }
        if (in_array('wel_matrix', $sheets, true)) {
            self::sheetMatrix($addSheet('Welfare'), $year, 'WELFARE', $centerId, false);
        }
        if (in_array('reg_arrears', $sheets, true)) {
            self::sheetMatrix($addSheet('Reg Arrears'), $year, 'REGISTRATION', $centerId, true);
        }
        if (in_array('wel_arrears', $sheets, true)) {
            self::sheetMatrix($addSheet('Wel Arrears'), $year, 'WELFARE', $centerId, true);
        }
        if (in_array('center_report', $sheets, true)) {
            self::sheetAggregate($addSheet('By Jumuiya'), $year, 'jumuiya', $centerId);
        }
        if (in_array('parish_report', $sheets, true)) {
            self::sheetAggregate($addSheet('By Center'), $year, 'center', null);
        }
        if (in_array('payments', $sheets, true)) {
            self::sheetPayments($addSheet('Payments'), $year, $centerId);
        }
        if (in_array('members', $sheets, true)) {
            self::sheetMembers($addSheet('Members'), $centerId);
        }

        // Ensure at least one sheet exists
        if ($ss->getSheetCount() === 0) {
            $ss->createSheet()->setTitle('Empty');
        }

        $ss->setActiveSheetIndex(0);

        $filename = sprintf('cwa-pack-%d-%s.xlsx', $year, date('Ymd-His'));
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($ss);
        $writer->save('php://output');
        exit;
    }

    // ---------------------------------------------------------------------
    // Sheet builders
    // ---------------------------------------------------------------------

    private static function sheetDashboard($sheet, int $year): void
    {
        $d = Report::dashboard($year);

        $sheet->setCellValue('A1', 'Catholic Women Association');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A2', "Dashboard — {$year}");
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A2')->getFont()->setItalic(true);
        $sheet->getStyle('A2')->getFont()->getColor()->setARGB('666666');

        $r = 4;
        self::kv($sheet, $r++, 'Members',           $d['counts']['members']);
        self::kv($sheet, $r++, 'Active members',    $d['counts']['active_members']);
        self::kv($sheet, $r++, 'New this year',     $d['counts']['new_members']);
        self::kv($sheet, $r++, 'Centers',           $d['counts']['centers']);
        self::kv($sheet, $r++, 'Jumuiyas',          $d['counts']['jumuiyas']);
        self::kv($sheet, $r++, 'Users',             $d['counts']['users']);

        $r += 1;
        $sheet->setCellValue("A{$r}", 'Registration');
        $sheet->getStyle("A{$r}")->getFont()->setBold(true);
        $r++;
        self::kvMoney($sheet, $r++, 'Expected',  $d['registration']['due']);
        self::kvMoney($sheet, $r++, 'Collected', $d['registration']['paid']);
        self::kvMoney($sheet, $r++, 'Balance',   $d['registration']['balance']);
        self::kv($sheet, $r++, 'Collection %',   $d['registration']['pct'] . '%');

        $r += 1;
        $sheet->setCellValue("A{$r}", 'Welfare');
        $sheet->getStyle("A{$r}")->getFont()->setBold(true);
        $r++;
        self::kvMoney($sheet, $r++, 'Expected',  $d['welfare']['due']);
        self::kvMoney($sheet, $r++, 'Collected', $d['welfare']['paid']);
        self::kvMoney($sheet, $r++, 'Balance',   $d['welfare']['balance']);
        self::kv($sheet, $r++, 'Collection %',   $d['welfare']['pct'] . '%');

        $r += 1;
        $sheet->setCellValue("A{$r}", 'Arrears');
        $sheet->getStyle("A{$r}")->getFont()->setBold(true);
        $r++;
        self::kv($sheet, $r++, 'Members in registration arrears', $d['arrears']['reg_members']);
        self::kv($sheet, $r++, 'Members in welfare arrears',      $d['arrears']['wel_members']);
        self::kvMoney($sheet, $r++, 'Total outstanding',          $d['arrears']['total_balance']);

        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);
    }

    private static function sheetMatrix($sheet, int $year, string $type, ?int $centerId, bool $arrearsOnly): void
    {
        $f = [
            'year'      => $year,
            'center_id' => $centerId,
            'status'    => '',
            'q'         => '',
        ];
        $report = $arrearsOnly
            ? Report::balanceMatrix($f + ['type' => $type])
            : Report::memberMatrix($f + ['type' => $type]);

        $sheet->setCellValue('A1', ucfirst(strtolower($type)) . ($arrearsOnly ? ' Arrears' : '') . " — {$year}");
        $sheet->mergeCells('A1:Q1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headers = ['Member', 'Code', 'Jumuiya', 'Center', 'Status'];
        if ($type === 'REGISTRATION' && !$arrearsOnly) {
            $headers[] = 'Renewal';
            $headers[] = 'Card';
        } else {
            $headers[] = 'Renewal';
            $headers[] = 'Card';
        }
        for ($m = 1; $m <= 12; $m++) $headers[] = date('M', mktime(0, 0, 0, $m, 1));
        $headers[] = 'Due';
        $headers[] = 'Paid';
        $headers[] = 'Balance';

        $sheet->fromArray($headers, null, 'A3');
        self::headerStyle($sheet, 'A3:' . self::col(count($headers) - 1) . '3');

        $row = 4;
        foreach ($report['rows'] as $r) {
            $m = $r['member'];
            $line = [
                $m['full_name'],
                $m['member_code'],
                $m['jumuiya_name'],
                $m['center_name'],
                $m['status'],
                $arrearsOnly ? (float)$r['balance']['renewal'] : (float)$r['paid']['renewal'],
                $arrearsOnly ? (float)$r['balance']['card']    : (float)$r['paid']['card'],
            ];
            for ($mo = 1; $mo <= 12; $mo++) {
                $line[] = $arrearsOnly
                    ? (float)$r['balance']['months'][$mo]
                    : (float)$r['paid']['months'][$mo];
            }
            $line[] = (float)$r['totals']['due'];
            $line[] = (float)$r['totals']['paid'];
            $line[] = (float)$r['totals']['balance'];
            $sheet->fromArray($line, null, 'A' . $row);
            $row++;
        }

        // Totals row
        $sheet->setCellValue('A' . $row, 'TOTALS');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->setCellValue(self::col(count($headers) - 3) . $row, (float)$report['totals']['due']);
        $sheet->setCellValue(self::col(count($headers) - 2) . $row, (float)$report['totals']['paid']);
        $sheet->setCellValue(self::col(count($headers) - 1) . $row, (float)$report['totals']['balance']);

        // Format numeric columns as money
        $lastCol = self::col(count($headers) - 1);
        $sheet->getStyle("F4:{$lastCol}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

        // Widths
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(12);
        for ($c = 5; $c < count($headers); $c++) {
            $sheet->getColumnDimension(self::col($c))->setWidth(10);
        }

        $sheet->freezePane('F4');
    }

    private static function sheetAggregate($sheet, int $year, string $group, ?int $centerId): void
    {
        $f = [
            'year'      => $year,
            'center_id' => $centerId,
            'status'    => '',
            'q'         => '',
        ];
        $report = Report::aggregate($f + ['group' => $group, 'type' => 'BOTH']);

        $sheet->setCellValue('A1', ($group === 'jumuiya' ? 'By Jumuiya' : 'By Center') . " — {$year}");
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headers = [
            $group === 'jumuiya' ? 'Jumuiya' : 'Center',
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
        ];
        $sheet->fromArray($headers, null, 'A3');
        self::headerStyle($sheet, 'A3:K3');

        $row = 4;
        foreach ($report['rows'] as $r) {
            $sheet->fromArray([
                $r['label'],
                $r['members'],
                $r['reg_due'],
                $r['reg_paid'],
                $r['reg_balance'],
                $r['wel_due'],
                $r['wel_paid'],
                $r['wel_balance'],
                $r['due'],
                $r['paid'],
                $r['balance'],
            ], null, 'A' . $row);
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'TOTALS');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('B' . $row, (int)$report['totals']['members']);
        $sheet->setCellValue('C' . $row, (float)$report['totals']['reg_due']);
        $sheet->setCellValue('D' . $row, (float)$report['totals']['reg_paid']);
        $sheet->setCellValue('E' . $row, (float)$report['totals']['reg_balance']);
        $sheet->setCellValue('F' . $row, (float)$report['totals']['wel_due']);
        $sheet->setCellValue('G' . $row, (float)$report['totals']['wel_paid']);
        $sheet->setCellValue('H' . $row, (float)$report['totals']['wel_balance']);
        $sheet->setCellValue('I' . $row, (float)$report['totals']['due']);
        $sheet->setCellValue('J' . $row, (float)$report['totals']['paid']);
        $sheet->setCellValue('K' . $row, (float)$report['totals']['balance']);

        $sheet->getStyle("C4:K{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getColumnDimension('A')->setWidth(28);
        for ($c = 1; $c <= 10; $c++) {
            $sheet->getColumnDimension(self::col($c))->setWidth(14);
        }
    }

    private static function sheetPayments($sheet, int $year, ?int $centerId): void
    {
        $sql = "SELECT p.receipt_no, p.payment_date, p.payment_type, p.payment_method,
                       p.reference_no, p.amount, p.status,
                       m.full_name, m.member_code,
                       j.name AS jumuiya_name, c.name AS center_name,
                       u.full_name AS entered_by_name
                  FROM payments p
                  JOIN members m ON m.id = p.member_id
                  JOIN jumuiyas j ON j.id = m.jumuiya_id
                  JOIN centers c ON c.id = j.center_id
                  LEFT JOIN users u ON u.id = p.entered_by
                 WHERE p.year = :y";
        $params = ['y' => $year];
        if ($centerId) {
            $sql .= " AND c.id = :cid";
            $params['cid'] = $centerId;
        }
        $sql .= " ORDER BY p.payment_date DESC, p.id DESC LIMIT 20000";

        $rows = Db::all($sql, $params);

        $sheet->setCellValue('A1', "Payments — {$year}");
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headers = [
            'Receipt',
            'Date',
            'Member',
            'Code',
            'Jumuiya',
            'Center',
            'Type',
            'Method',
            'Reference',
            'Amount',
            'Status',
            'Entered by',
        ];
        $sheet->fromArray($headers, null, 'A3');
        self::headerStyle($sheet, 'A3:L3');

        $row = 4;
        foreach ($rows as $p) {
            $sheet->fromArray([
                $p['receipt_no'],
                $p['payment_date'],
                $p['full_name'],
                $p['member_code'],
                $p['jumuiya_name'],
                $p['center_name'],
                $p['payment_type'],
                $p['payment_method'],
                $p['reference_no'],
                (float)$p['amount'],
                $p['status'],
                $p['entered_by_name'],
            ], null, 'A' . $row);
            $row++;
        }

        $sheet->getStyle("J4:J{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(14);
        $sheet->freezePane('A4');
    }

    private static function sheetMembers($sheet, ?int $centerId): void
    {
        $sql = "SELECT m.member_code, m.full_name, m.phone, m.id_number, m.gender,
                       m.date_of_birth, m.join_date, m.status,
                       j.name AS jumuiya_name, c.name AS center_name
                  FROM members m
                  JOIN jumuiyas j ON j.id = m.jumuiya_id
                  JOIN centers c ON c.id = j.center_id
                 WHERE 1=1";
        $params = [];
        if ($centerId) {
            $sql .= " AND c.id = :cid";
            $params['cid'] = $centerId;
        }
        $sql .= " ORDER BY c.name, j.name, m.full_name";

        $rows = Db::all($sql, $params);

        $sheet->setCellValue('A1', 'Members');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headers = [
            'Code',
            'Full name',
            'Phone',
            'ID number',
            'Gender',
            'DOB',
            'Join date',
            'Status',
            'Jumuiya',
            'Center',
        ];
        $sheet->fromArray($headers, null, 'A3');
        self::headerStyle($sheet, 'A3:J3');

        $row = 4;
        foreach ($rows as $m) {
            $sheet->fromArray([
                $m['member_code'],
                $m['full_name'],
                $m['phone'],
                $m['id_number'],
                $m['gender'],
                $m['date_of_birth'],
                $m['join_date'],
                $m['status'],
                $m['jumuiya_name'],
                $m['center_name'],
            ], null, 'A' . $row);
            $row++;
        }

        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(18);
        $sheet->freezePane('A4');
    }

    // ---------------------------------------------------------------------
    // Small helpers
    // ---------------------------------------------------------------------

    private static function kv($sheet, int $row, string $label, $value): void
    {
        $sheet->setCellValue("A{$row}", $label);
        $sheet->setCellValue("B{$row}", $value);
    }
    private static function kvMoney($sheet, int $row, string $label, $value): void
    {
        $sheet->setCellValue("A{$row}", $label);
        $sheet->setCellValue("B{$row}", (float)$value);
        $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
    }

    private static function headerStyle($sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('F2F2F2');
        $sheet->getStyle($range)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /** Convert 0-based column index to letters (0=A). */
    private static function col(int $idx): string
    {
        $letters = '';
        $n = $idx;
        do {
            $letters = chr(65 + ($n % 26)) . $letters;
            $n = intdiv($n, 26) - 1;
        } while ($n >= 0);
        return $letters;
    }
}
