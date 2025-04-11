<?php

namespace App\Services\AI;

use Carbon\Carbon;
use Illuminate\Support\Str;

class DateRangeAnalyzer
{
    private array $monthNames = [
        'ocak' => 1, 'şubat' => 2, 'mart' => 3, 'nisan' => 4, 'mayıs' => 5, 'haziran' => 6,
        'temmuz' => 7, 'ağustos' => 8, 'eylül' => 9, 'ekim' => 10, 'kasım' => 11, 'aralık' => 12,
        'ock' => 1, 'şbt' => 2, 'mar' => 3, 'nis' => 4, 'may' => 5, 'haz' => 6,
        'tem' => 7, 'ağu' => 8, 'eyl' => 9, 'ekm' => 10, 'kas' => 11, 'ara' => 12
    ];

    public function analyze(string $message): array
    {
        $message = mb_strtolower($message);
        
        // Yıl tespiti
        preg_match('/\b(20\d{2})\b/', $message, $yearMatches);
        $year = $yearMatches[1] ?? date('Y');
        
        // Ay tespiti
        $month = null;
        foreach ($this->monthNames as $name => $number) {
            if (Str::contains($message, $name)) {
                $month = $number;
                break;
            }
        }

        // Özel durumlar
        if (Str::contains($message, 'bu yıl')) {
            $year = date('Y');
        }
        
        if (Str::contains($message, 'geçen yıl')) {
            $year = (int)date('Y') - 1;
        }

        // Tarih aralığı belirleme
        if ($month) {
            // Belirli bir ay için
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
        } else {
            // Yıl için
            $start = Carbon::create($year, 1, 1)->startOfYear();
            
            // Eğer gelecek yıl ise, bugüne kadar
            if ($year > date('Y')) {
                $end = now();
            } else {
                $end = $start->copy()->endOfYear();
            }
        }

        // "... dan bu yana" veya "... den beri" gibi durumlar
        if (Str::contains($message, ['dan bu yana', 'den bu yana', 'dan beri', 'den beri'])) {
            if (preg_match('/(\d+)\s*\.\s*ay/', $message, $matches)) {
                $start = now()->subMonths($matches[1])->startOfMonth();
            } elseif ($month) {
                $start = Carbon::create($year, $month, 1)->startOfMonth();
            }
            $end = now();
        }

        // Başlangıç tarihi bugünden sonra ise bugüne çek
        if ($start->isFuture()) {
            $start = now()->startOfDay();
        }

        // Bitiş tarihi başlangıçtan önce ise başlangıca eşitle
        if ($end->lt($start)) {
            $end = $start->copy()->endOfMonth();
        }

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'is_single_month' => $month !== null,
            'year' => $year,
            'month' => $month,
            'period_type' => $this->determinePeriodType($start, $end)
        ];
    }

    private function determinePeriodType(Carbon $start, Carbon $end): string
    {
        if ($start->format('Y-m') === $end->format('Y-m')) {
            return 'month';
        }

        if ($start->format('Y') === $end->format('Y') && 
            $start->format('m') === '01' && 
            $end->format('m') === '12') {
            return 'year';
        }

        return 'custom';
    }
} 