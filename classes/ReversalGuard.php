<?php
class ReversalGuard
{
    /** @return array{allowed:bool, needs_approval:bool, reason:string} */
    public static function canVoid(int $paymentId, string $reason): array
    {
        $p = Payment::find($paymentId);
        if (!$p) return ['allowed' => false, 'needs_approval' => false, 'reason' => 'Payment not found.'];
        if ($p['status'] !== 'ACTIVE') {
            return ['allowed' => false, 'needs_approval' => false, 'reason' => 'Payment is not ACTIVE.'];
        }

        $minChars = setting_int('reversal_min_reason_chars', 10);
        if (mb_strlen(trim($reason)) < $minChars) {
            return [
                'allowed' => false,
                'needs_approval' => false,
                'reason' => "Reason must be at least {$minChars} characters.",
            ];
        }

        $requiresApproval = setting_bool('reversal_requires_approval', false);
        if (!$requiresApproval) {
            return ['allowed' => true, 'needs_approval' => false, 'reason' => ''];
        }

        $role = $_SESSION['user']['role_name'] ?? '';
        $amount = (float)$p['amount'];

        $cap = null;
        if ($role === 'CENTER_ADMIN') $cap = setting_int('reversal_cap_center_admin', 5000);
        if ($role === 'PARISH_ADMIN') $cap = setting_int('reversal_cap_parish_admin', 50000);
        if ($role === 'SUPER_ADMIN')  $cap = null; // unlimited

        if ($cap !== null && $amount > $cap) {
            return [
                'allowed' => false,
                'needs_approval' => true,
                'reason' => 'Amount exceeds your void cap. Approval required.',
            ];
        }

        return ['allowed' => true, 'needs_approval' => false, 'reason' => ''];
    }

    public static function createApproval(int $paymentId, string $reason): int
    {
        $p = Payment::find($paymentId);
        if (!$p) throw new RuntimeException('Payment not found.');
        return Db::insert('approval_requests', [
            'request_type' => 'VOID_PAYMENT',
            'payment_id'   => $paymentId,
            'requested_by' => Auth::id(),
            'reason'       => $reason,
            'amount'       => (float)$p['amount'],
            'status'       => 'PENDING',
        ]);
    }
}
