<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'commission_rate',
        'commission_amount'
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2'
    ];

    /**
     * Belirli bir kullanıcının komisyon istatistiklerini getirir
     */
    public static function getUserStats(int $userId, ?string $period = null): array
    {
        $query = self::where('user_id', $userId);

        // Dönem filtresi
        if ($period === 'this_month') {
            $query->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
        } elseif ($period === 'last_month') {
            $query->whereMonth('created_at', now()->subMonth()->month)
                  ->whereYear('created_at', now()->subMonth()->year);
        }

        // Toplam komisyon
        $totalStats = $query->select([
            DB::raw('SUM(commission_amount) as total_commission'),
            DB::raw('COUNT(*) as total_transactions')
        ])->first();

        // Ödemeler
        $payoutStats = CommissionPayout::where('user_id', $userId)
            ->when($period === 'this_month', function ($q) {
                $q->whereMonth('payment_date', now()->month)
                  ->whereYear('payment_date', now()->year);
            })
            ->when($period === 'last_month', function ($q) {
                $q->whereMonth('payment_date', now()->subMonth()->month)
                  ->whereYear('payment_date', now()->subMonth()->year);
            })
            ->select(DB::raw('SUM(amount) as total_paid'))
            ->first();

        $totalCommission = $totalStats->total_commission ?? 0;
        $totalPaid = $payoutStats->total_paid ?? 0;

        return [
            'total_commission' => $totalCommission,
            'total_paid' => $totalPaid,
            'total_pending' => max(0, $totalCommission - $totalPaid),
            'total_transactions' => $totalStats->total_transactions ?? 0
        ];
    }

    /**
     * Komisyonu kazanan kullanıcı.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Komisyonun ilişkili olduğu işlem.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
} 