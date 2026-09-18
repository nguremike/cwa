<?php

/**
 * Rules:
 *  - If the year is CLOSED, schedules are locked regardless.
 *  - If the year is OPEN and the (year,type) has no payments, schedules are editable.
 *  - If a payment exists on (year,type), the schedule is LOCKED unless an
 *    explicit unlock row exists in contribution_schedule_locks.
 */

function schedule_has_activity(int $year, string $type): bool
{
    $row = Db::one(
        "SELECT payment_count FROM contribution_activity
          WHERE year = :y AND contribution_type = :t",
        ['y' => $year, 't' => $type]
    );
    return $row && (int)$row['payment_count'] > 0;
}

function schedule_is_unlocked(int $year, string $type): bool
{
    return (bool)Db::one(
        "SELECT id FROM contribution_schedule_locks
          WHERE year = :y AND contribution_type = :t",
        ['y' => $year, 't' => $type]
    );
}

/**
 * @return array{editable:bool, reason:string}
 */
function schedule_edit_state(int $year, string $type): array
{
    $fy = FinancialYear::byYear($year);
    if (!$fy) {
        return ['editable' => false, 'reason' => 'Financial year does not exist.'];
    }
    if ($fy['status'] === 'CLOSED') {
        return ['editable' => false, 'reason' => 'Financial year is closed.'];
    }
    if (!schedule_has_activity($year, $type)) {
        return ['editable' => true, 'reason' => 'No payments on this type yet.'];
    }
    if (schedule_is_unlocked($year, $type)) {
        return ['editable' => true, 'reason' => 'Explicitly unlocked after payments began.'];
    }
    return [
        'editable' => false,
        'reason'   => 'Payments already recorded for this year and type. Unlock to edit.',
    ];
}
