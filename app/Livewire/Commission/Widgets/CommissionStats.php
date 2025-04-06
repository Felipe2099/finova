<?php

namespace App\Livewire\Commission\Widgets;

use App\Models\Commission;
use App\Models\CommissionPayout;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class CommissionStats extends Widget
{
    protected static string $view = 'livewire.commission.widgets.commission-stats';

    public $user;

    public function mount($user)
    {
        $this->user = $user;
    }

    #[On('commission-stats-updated')]
    public function refresh(): void
    {
        $this->dispatch('refresh');
    }

    public function getStats(): array
    {
        $now = Carbon::now();
        $currentMonth = $now->format('Y-m');
        $lastMonth = $now->copy()->subMonth()->format('Y-m');

        // Bu ay kazanılan komisyon - işlem tarihine göre
        $currentMonthCommission = Commission::query()
            ->where('user_id', $this->user->id)
            ->whereHas('transaction', function ($query) use ($now) {
                $query->whereYear('date', $now->year)
                      ->whereMonth('date', $now->month);
            })
            ->sum('commission_amount');

        // Bu ay yapılan ödeme
        $currentMonthPayout = CommissionPayout::query()
            ->where('user_id', $this->user->id)
            ->whereYear('payment_date', $now->year)
            ->whereMonth('payment_date', $now->month)
            ->sum('amount');

        // Bu ay kalan ödeme
        $currentMonthRemaining = $currentMonthCommission - $currentMonthPayout;

        // Geçen ay kazanılan komisyon - işlem tarihine göre
        $lastMonthCommission = Commission::query()
            ->where('user_id', $this->user->id)
            ->whereHas('transaction', function ($query) use ($now) {
                $query->whereYear('date', $now->copy()->subMonth()->year)
                      ->whereMonth('date', $now->copy()->subMonth()->month);
            })
            ->sum('commission_amount');

        // Geçen ay yapılan ödeme
        $lastMonthPayout = CommissionPayout::query()
            ->where('user_id', $this->user->id)
            ->whereYear('payment_date', $now->copy()->subMonth()->year)
            ->whereMonth('payment_date', $now->copy()->subMonth()->month)
            ->sum('amount');

        // Geçen ay kalan ödeme
        $lastMonthRemaining = $lastMonthCommission - $lastMonthPayout;

        // Toplam kazanılan komisyon
        $totalCommission = Commission::query()
            ->where('user_id', $this->user->id)
            ->sum('commission_amount');

        // Toplam yapılan ödeme
        $totalPayout = CommissionPayout::query()
            ->where('user_id', $this->user->id)
            ->sum('amount');

        // Toplam kalan ödeme
        $totalRemaining = $totalCommission - $totalPayout;

        return [
            // Bu ay istatistikleri
            [
                'label' => 'Bu Ay Kazanılan',
                'value' => number_format($currentMonthCommission, 2, ',', '.') . ' ₺',
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'success'
            ],
            [
                'label' => 'Bu Ay Yapılan Ödeme',
                'value' => number_format($currentMonthPayout, 2, ',', '.') . ' ₺',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'success'
            ],
            [
                'label' => 'Bu Ay Kalan Ödeme',
                'value' => number_format($currentMonthRemaining, 2, ',', '.') . ' ₺',
                'icon' => 'heroicon-o-calculator',
                'color' => 'success'
            ],
            
            // Geçen ay istatistikleri
            [
                'label' => 'Geçen Ay Kazanılan',
                'value' => number_format($lastMonthCommission, 2, ',', '.') . ' ₺',
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'warning'
            ],
            [
                'label' => 'Geçen Ay Yapılan Ödeme',
                'value' => number_format($lastMonthPayout, 2, ',', '.') . ' ₺',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'warning'
            ],
            [
                'label' => 'Geçen Ay Kalan Ödeme',
                'value' => number_format($lastMonthRemaining, 2, ',', '.') . ' ₺',
                'icon' => 'heroicon-o-calculator',
                'color' => 'warning'
            ],
            
            // Toplam istatistikler
            [
                'label' => 'Toplam Kazanılan',
                'value' => number_format($totalCommission, 2, ',', '.') . ' ₺',
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'primary'
            ],
            [
                'label' => 'Toplam Yapılan Ödeme',
                'value' => number_format($totalPayout, 2, ',', '.') . ' ₺',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'primary'
            ],
            [
                'label' => 'Toplam Kalan Ödeme',
                'value' => number_format($totalRemaining, 2, ',', '.') . ' ₺',
                'icon' => 'heroicon-o-calculator',
                'color' => 'primary'
            ]
        ];
    }
} 