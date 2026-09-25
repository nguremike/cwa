<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../classes/Audit.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

echo "This will rewrite member_code for all members as PREFIX-YYYY-NNNNNN.\n";
echo "Take a backup first (bin/backup-db.sh).\n";
echo "Type MIGRATE to proceed: ";
$answer = trim(fgets(STDIN));
if ($answer !== 'MIGRATE') {
    exit("Aborted.\n");
}

Db::begin();
try {
    $prefix = 'CWA';
    $pad    = 6;

    // Group by year(join_date), ordered by id so the earliest rows get the lowest numbers.
    $members = Db::all(
        "SELECT id, member_code, join_date
           FROM members
          ORDER BY YEAR(join_date) ASC, id ASC"
    );

    $perYearNext = [];
    $updates = [];

    foreach ($members as $m) {
        $year = (int)date('Y', strtotime($m['join_date']));
        if (!isset($perYearNext[$year])) $perYearNext[$year] = 0;

        $perYearNext[$year]++;
        $n = $perYearNext[$year];
        $newCode = sprintf('%s-%d-%s', $prefix, $year, str_pad((string)$n, $pad, '0', STR_PAD_LEFT));

        if ($newCode !== $m['member_code']) {
            $updates[] = ['id' => (int)$m['id'], 'old' => $m['member_code'], 'new' => $newCode];
        }
    }

    foreach ($updates as $u) {
        Db::update('members', ['member_code' => $u['new']], 'id = :id', ['id' => $u['id']]);
        Audit::log(
            'UPDATE',
            'members',
            $u['id'],
            ['member_code' => $u['old']],
            ['member_code' => $u['new'], 'reason' => 'Year-prefixed code migration']
        );
    }

    // Reset the sequences to the highest number used per year
    Db::q("DELETE FROM member_code_seq WHERE prefix = :p", ['p' => $prefix]);
    foreach ($perYearNext as $year => $last) {
        Db::insert('member_code_seq', [
            'prefix'     => $prefix,
            'year'       => $year,
            'last_value' => $last,
        ]);
    }

    Db::commit();
    echo "Rewrote " . count($updates) . " member codes.\n";
    foreach ($perYearNext as $year => $last) {
        echo "  {$year}: next will be {$prefix}-{$year}-" . str_pad((string)($last + 1), $pad, '0', STR_PAD_LEFT) . "\n";
    }
} catch (Throwable $e) {
    Db::rollback();
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
