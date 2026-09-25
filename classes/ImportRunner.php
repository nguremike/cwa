<?php
class ImportRunner
{
    /**
     * @param array $stage {
     *   type: string, year: int, file: string, stored: string,
     *   rows: array (valid rows), skipped: array
     * }
     * @return array{batch_id:int, imported:int, skipped:int, total_amount:float}
     */
    public static function run(array $stage): array
    {
        $type   = strtoupper($stage['type']);
        $year   = (int)$stage['year'];
        $rows   = $stage['rows'] ?? [];
        $skipped = $stage['skipped'] ?? [];
        $file   = (string)($stage['file'] ?? 'unknown.xlsx');

        if (!in_array($type, ['REGISTRATION', 'WELFARE'], true)) {
            throw new RuntimeException('Invalid type.');
        }
        if (!$rows) throw new RuntimeException('Nothing to import.');

        Db::begin();
        try {
            $batchRef = self::nextBatchRef($year);
            $batchId  = Db::insert('import_batches', [
                'batch_ref'         => $batchRef,
                'contribution_type' => $type,
                'year'              => $year,
                'source_file'       => $file,
                'rows_total'        => count($rows) + count($skipped),
                'rows_imported'     => 0,
                'rows_skipped'      => count($skipped),
                'total_amount'      => 0,
                'status'            => 'POSTED',
                'uploaded_by'       => Auth::id(),
            ]);

            $imported    = 0;
            $totalAmount = 0.0;

            foreach ($rows as $r) {
                // Build plan
                $plan = AllocationEngine::plan(
                    (int)$r['member_id'],
                    $year,
                    $type,
                    (float)$r['amount']
                );

                // Insert payment + allocations + advance deltas
                $paymentId = self::insertPayment(
                    (int)$r['member_id'],
                    $year,
                    $r['date'],
                    (float)$r['amount'],
                    $type,
                    $r['method'],
                    $r['ref'] ?: null,
                    $r['notes'] ?: null,
                    $plan,
                    $batchId,
                    Auth::id()
                );

                $imported++;
                $totalAmount += (float)$r['amount'];
            }

            Db::update('import_batches', [
                'rows_imported' => $imported,
                'total_amount'  => round($totalAmount, 2),
            ], 'id = :id', ['id' => $batchId]);

            Db::commit();

            return [
                'batch_id'     => $batchId,
                'imported'     => $imported,
                'skipped'      => count($skipped),
                'total_amount' => round($totalAmount, 2),
            ];
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * Insert one payment row + its allocations, mirroring Payment::createWithPlan()
     * but tagging the payment with the batch id. Runs inside the caller's transaction.
     */
    private static function insertPayment(
        int $memberId,
        int $year,
        string $date,
        float $amount,
        string $type,
        string $method,
        ?string $ref,
        ?string $notes,
        array $plan,
        int $batchId,
        int $userId
    ): int {
        $receipt = AllocationEngine::nextReceipt($year);

        $paymentId = Db::insert('payments', [
            'receipt_no'      => $receipt,
            'member_id'       => $memberId,
            'year'            => $year,
            'payment_date'    => $date,
            'amount'          => $amount,
            'payment_type'    => $type,
            'payment_method'  => $method,
            'reference_no'    => $ref,
            'notes'           => $notes,
            'status'          => 'ACTIVE',
            'entered_by'      => $userId,
            'import_batch_id' => $batchId,
        ]);

        AllocationEngine::commitPlan($paymentId, $memberId, $year, $type, $plan, $userId);

        Audit::log('PAYMENT', 'payments', $paymentId, null, [
            'receipt_no'      => $receipt,
            'member_id'       => $memberId,
            'year'            => $year,
            'amount'          => $amount,
            'type'            => $type,
            'import_batch_id' => $batchId,
        ]);

        return $paymentId;
    }

    /** Atomic batch reference: IMP-YYYY-000001 */
    private static function nextBatchRef(int $year): string
    {
        Db::q(
            "INSERT INTO import_seq (year, last_value)
             VALUES (:y, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE last_value = LAST_INSERT_ID(last_value + 1)",
            ['y' => $year]
        );
        $n = (int)Db::conn()->lastInsertId();
        if ($n <= 0) throw new RuntimeException('Import sequence did not advance.');
        return sprintf('IMP-%d-%06d', $year, $n);
    }
}
