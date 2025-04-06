<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\CommissionPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    public function processPayment(User $user, array $commissionIds, float $amount, string $paymentDate, ?string $description = null): void
    {
        DB::transaction(function () use ($user, $commissionIds, $amount, $paymentDate, $description) {
            // Ödeme kaydını oluştur
            $payment = CommissionPayment::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'description' => $description,
            ]);

            // Seçilen komisyonları ödenmiş olarak işaretle
            Commission::whereIn('id', $commissionIds)
                ->update([
                    'payment_id' => $payment->id,
                    'status' => 'paid',
                    'paid_at' => $paymentDate,
                ]);
        });
    }

    public function getStats(User $user): array
    {
        $commissions = Commission::where('user_id', $user->id);

        return [
            'total_commission' => $commissions->sum('amount'),
            'total_paid' => $commissions->where('status', 'paid')->sum('amount'),
            'total_pending' => $commissions->where('status', 'pending')->sum('amount'),
            'total_transactions' => $commissions->count(),
        ];
    }
} 