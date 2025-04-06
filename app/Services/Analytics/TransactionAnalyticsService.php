<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * İşlem analiz servisi
 * 
 * İşlemlerin analizi ve raporlanması için gerekli metodları içerir.
 * Gelir-gider özetleri, nakit akışı, kategori bazlı harcamalar ve diğer analizleri sağlar.
 */
class TransactionAnalyticsService
{
    /**
     * Belirli bir dönem için gelir-gider özetini getirir
     * 
     * @param string $period Dönem tipi (day, week, month, quarter, year)
     * @param Carbon|null $date Referans tarih
     * @return array Gelir-gider özeti ve değişim yüzdeleri
     */
    public function getSummary(string $period = 'month', ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::now();
        $startDate = $this->getPeriodStartDate($date, $period);
        $endDate = $this->getPeriodEndDate($date, $period);
        
        // Current period data
        $currentPeriodData = $this->getTransactionTotals($startDate, $endDate);
        
        // Previous period for comparison
        $previousStartDate = $this->getPreviousPeriodStartDate($date, $period);
        $previousEndDate = $this->getPreviousPeriodEndDate($date, $period);
        $previousPeriodData = $this->getTransactionTotals($previousStartDate, $previousEndDate);
        
        // Calculate percentages
        $incomeChange = $this->calculatePercentageChange(
            $previousPeriodData['income'] ?? 0, 
            $currentPeriodData['income'] ?? 0
        );
        
        $expenseChange = $this->calculatePercentageChange(
            $previousPeriodData['expense'] ?? 0, 
            $currentPeriodData['expense'] ?? 0
        );
        
        $balanceChange = $this->calculatePercentageChange(
            ($previousPeriodData['income'] ?? 0) - ($previousPeriodData['expense'] ?? 0),
            ($currentPeriodData['income'] ?? 0) - ($currentPeriodData['expense'] ?? 0)
        );
        
        return [
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'income' => [
                'amount' => $currentPeriodData['income'] ?? 0,
                'change_percentage' => $incomeChange,
                'previous_amount' => $previousPeriodData['income'] ?? 0,
            ],
            'expense' => [
                'amount' => $currentPeriodData['expense'] ?? 0,
                'change_percentage' => $expenseChange,
                'previous_amount' => $previousPeriodData['expense'] ?? 0,
            ],
            'balance' => [
                'amount' => ($currentPeriodData['income'] ?? 0) - ($currentPeriodData['expense'] ?? 0),
                'change_percentage' => $balanceChange,
                'previous_amount' => ($previousPeriodData['income'] ?? 0) - ($previousPeriodData['expense'] ?? 0),
            ],
        ];
    }
    
    /**
     * Belirli bir ay için aylık bakiye bilgilerini getirir
     * 
     * @param int $year Yıl
     * @param int $month Ay
     * @param int|null $userId Kullanıcı ID'si
     * @return array Aylık gelir, gider ve transfer bilgileri
     */
    public function getMonthlyBalance(int $year, int $month, ?int $userId = null): array
    {
        $userId = $userId ?? auth()->id();
        
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();
        
        $income = Transaction::where('user_id', $userId)
            ->where('type', 'income')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
            
        $expenses = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
            
        $transfers = Transaction::where('user_id', $userId)
            ->where('type', 'transfer')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
            
        return [
            'income' => $income,
            'expenses' => $expenses,
            'transfers' => $transfers,
            'net' => $income - $expenses,
            'period' => $startDate->format('F Y')
        ];
    }
    
    /**
     * Belirli bir dönem için nakit akışı verilerini getirir
     * 
     * @param int|null $userId Kullanıcı ID'si
     * @param Carbon|null $startDate Başlangıç tarihi
     * @param Carbon|null $endDate Bitiş tarihi
     * @return array Aylık nakit akışı verileri
     */
    public function getCashFlow(?int $userId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $userId = $userId ?? auth()->id();
        $startDate = $startDate ?? Carbon::now()->subMonths(12)->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();
        
        $transactions = Transaction::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($transaction) {
                return Carbon::parse($transaction->date)->format('Y-m');
            });
            
        $cashFlow = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $yearMonth = $currentDate->format('Y-m');
            
            $monthlyTransactions = $transactions[$yearMonth] ?? collect();
            
            $income = $monthlyTransactions->where('type', 'income')->sum('amount');
            $expenses = $monthlyTransactions->where('type', 'expense')->sum('amount');
            
            $cashFlow[] = [
                'period' => $currentDate->format('M Y'),
                'income' => $income,
                'expenses' => $expenses,
                'net' => $income - $expenses
            ];
            
            $currentDate->addMonth();
        }
        
        return $cashFlow;
    }
    
    /**
     * Belirli bir tarih aralığı için işlem toplamlarını getirir
     * 
     * @param Carbon $startDate Başlangıç tarihi
     * @param Carbon $endDate Bitiş tarihi
     * @return array İşlem tiplerine göre toplamlar
     */
    private function getTransactionTotals(Carbon $startDate, Carbon $endDate): array
    {
        $results = Transaction::whereBetween('date', [$startDate, $endDate])
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->get()
            ->pluck('total', 'type')
            ->toArray();
            
        return $results;
    }
    
    /**
     * İki değer arasındaki yüzdelik değişimi hesaplar
     * 
     * @param float $oldValue Eski değer
     * @param float $newValue Yeni değer
     * @return float Yüzdelik değişim
     */
    private function calculatePercentageChange(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }
        
        return round((($newValue - $oldValue) / $oldValue) * 100, 2);
    }
    
    /**
     * Belirli bir dönem için başlangıç tarihini hesaplar
     * 
     * @param Carbon $date Referans tarih
     * @param string $period Dönem tipi
     * @return Carbon Başlangıç tarihi
     */
    private function getPeriodStartDate(Carbon $date, string $period): Carbon
    {
        switch ($period) {
            case 'day':
                return $date->copy()->startOfDay();
            case 'week':
                return $date->copy()->startOfWeek();
            case 'month':
                return $date->copy()->startOfMonth();
            case 'quarter':
                return $date->copy()->startOfQuarter();
            case 'year':
                return $date->copy()->startOfYear();
            default:
                return $date->copy()->startOfMonth();
        }
    }
    
    /**
     * Belirli bir dönem için bitiş tarihini hesaplar
     * 
     * @param Carbon $date Referans tarih
     * @param string $period Dönem tipi
     * @return Carbon Bitiş tarihi
     */
    private function getPeriodEndDate(Carbon $date, string $period): Carbon
    {
        switch ($period) {
            case 'day':
                return $date->copy()->endOfDay();
            case 'week':
                return $date->copy()->endOfWeek();
            case 'month':
                return $date->copy()->endOfMonth();
            case 'quarter':
                return $date->copy()->endOfQuarter();
            case 'year':
                return $date->copy()->endOfYear();
            default:
                return $date->copy()->endOfMonth();
        }
    }
    
    /**
     * Önceki dönemin başlangıç tarihini hesaplar
     * 
     * @param Carbon $date Referans tarih
     * @param string $period Dönem tipi
     * @return Carbon Önceki dönem başlangıç tarihi
     */
    private function getPreviousPeriodStartDate(Carbon $date, string $period): Carbon
    {
        switch ($period) {
            case 'day':
                return $date->copy()->subDay()->startOfDay();
            case 'week':
                return $date->copy()->subWeek()->startOfWeek();
            case 'month':
                return $date->copy()->subMonth()->startOfMonth();
            case 'quarter':
                return $date->copy()->subQuarter()->startOfQuarter();
            case 'year':
                return $date->copy()->subYear()->startOfYear();
            default:
                return $date->copy()->subMonth()->startOfMonth();
        }
    }
    
    /**
     * Önceki dönemin bitiş tarihini hesaplar
     * 
     * @param Carbon $date Referans tarih
     * @param string $period Dönem tipi
     * @return Carbon Önceki dönem bitiş tarihi
     */
    private function getPreviousPeriodEndDate(Carbon $date, string $period): Carbon
    {
        switch ($period) {
            case 'day':
                return $date->copy()->subDay()->endOfDay();
            case 'week':
                return $date->copy()->subWeek()->endOfWeek();
            case 'month':
                return $date->copy()->subMonth()->endOfMonth();
            case 'quarter':
                return $date->copy()->subQuarter()->endOfQuarter();
            case 'year':
                return $date->copy()->subYear()->endOfYear();
            default:
                return $date->copy()->subMonth()->endOfMonth();
        }
    }
    
    /**
     * Belirli bir dönem için kategori bazlı harcamaları getirir
     * 
     * @param string $period Dönem tipi
     * @param Carbon|null $date Referans tarih
     * @return Collection Kategori bazlı harcama verileri
     */
    public function getSpendingByCategory(string $period = 'month', ?Carbon $date = null): Collection
    {
        $date = $date ?? Carbon::now();
        $startDate = $this->getPeriodStartDate($date, $period);
        $endDate = $this->getPeriodEndDate($date, $period);
        
        return Transaction::where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->with('category:id,name')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category->name ?? 'Uncategorized',
                    'amount' => $item->total,
                ];
            });
    }
    
    /**
     * Belirli bir dönem için kategori bazlı gelirleri getirir
     * 
     * @param string $period Dönem tipi
     * @param Carbon|null $date Referans tarih
     * @return Collection Kategori bazlı gelir verileri
     */
    public function getIncomeByCategory(string $period = 'month', ?Carbon $date = null): Collection
    {
        $date = $date ?? Carbon::now();
        $startDate = $this->getPeriodStartDate($date, $period);
        $endDate = $this->getPeriodEndDate($date, $period);
        
        return Transaction::where('type', 'income')
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->with('category:id,name')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category->name ?? 'Uncategorized',
                    'amount' => $item->total,
                ];
            });
    }
    
    /**
     * Etiket bazlı harcama raporunu getirir
     * 
     * @param array $tags Etiketler
     * @param string $period Dönem tipi
     * @param Carbon|null $date Referans tarih
     * @return Collection Etiket bazlı harcama verileri
     */
    public function getSpendingByTags(array $tags, string $period = 'month', ?Carbon $date = null): Collection
    {
        $date = $date ?? Carbon::now();
        $startDate = $this->getPeriodStartDate($date, $period);
        $endDate = $this->getPeriodEndDate($date, $period);
        
        return Transaction::where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereJsonContains('tags', $tags)
            ->selectRaw('JSON_EXTRACT(tags, "$[0]") as tag, SUM(amount) as total')
            ->groupBy('tag')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'tag' => json_decode($item->tag),
                    'amount' => $item->total,
                ];
            });
    }
    
    /**
     * Bir yıl boyunca aylık işlem trendlerini getirir
     * 
     * @param int|null $year Yıl
     * @return array Aylık gelir-gider trendleri
     */
    public function getMonthlyTrends(int $year = null): array
    {
        $year = $year ?? Carbon::now()->year;
        $startDate = Carbon::createFromDate($year, 1, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, 12, 31)->endOfMonth();
        
        $monthlyData = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $currentStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $currentEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth();
            
            $totals = $this->getTransactionTotals($currentStart, $currentEnd);
            
            $monthlyData[] = [
                'month' => $currentStart->format('M'),
                'income' => $totals['income'] ?? 0,
                'expense' => $totals['expense'] ?? 0,
                'balance' => ($totals['income'] ?? 0) - ($totals['expense'] ?? 0),
            ];
        }
        
        return $monthlyData;
    }
    
    /**
     * Tüm hesaplar için hesap bazlı aktivite bilgilerini getirir
     * 
     * @param string $period Dönem tipi
     * @return Collection Hesap bazlı aktivite verileri
     */
    public function getAccountActivity(string $period = 'month'): Collection
    {
        $date = Carbon::now();
        $startDate = $this->getPeriodStartDate($date, $period);
        $endDate = $this->getPeriodEndDate($date, $period);
        
        return BankAccount::with(['transactions' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }])
        ->get()
        ->map(function ($account) {
            // Calculate totals
            $income = $account->transactions->where('type', 'income')->sum('amount');
            $expense = $account->transactions->where('type', 'expense')->sum('amount');
            
            return [
                'account_id' => $account->id,
                'account_name' => $account->bank_name,
                'current_balance' => $account->balance,
                'period_income' => $income,
                'period_expense' => $expense,
                'period_change' => $income - $expense,
                'transaction_count' => $account->transactions->count(),
            ];
        });
    }
    
    /**
     * Belirli bir hesap için bakiye geçmişini getirir
     * 
     * @param int $accountId Hesap ID'si
     * @param int $months Ay sayısı
     * @return array Günlük bakiye geçmişi
     */
    public function getAccountBalanceHistory(int $accountId, int $months = 6): array
    {
        $endDate = Carbon::now()->endOfDay();
        $startDate = Carbon::now()->subMonths($months)->startOfDay();
        
        $account = BankAccount::findOrFail($accountId);
        
        // Get all transactions affecting this account
        $incomeTransactions = Transaction::join('income_transactions', 'transactions.id', '=', 'income_transactions.transaction_id')
            ->where('income_transactions.bank_account_id', $accountId)
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->select('transactions.*')
            ->get();
            
        $expenseTransactions = Transaction::join('expense_transactions', 'transactions.id', '=', 'expense_transactions.transaction_id')
            ->where('expense_transactions.bank_account_id', $accountId)
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->select('transactions.*')
            ->get();
            
        $transferInTransactions = Transaction::join('transfer_transactions', 'transactions.id', '=', 'transfer_transactions.transaction_id')
            ->where('transfer_transactions.target_bank_account_id', $accountId)
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->select('transactions.*')
            ->get();
            
        $transferOutTransactions = Transaction::join('transfer_transactions', 'transactions.id', '=', 'transfer_transactions.transaction_id')
            ->where('transfer_transactions.source_bank_account_id', $accountId)
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->select('transactions.*')
            ->get();
            
        $allTransactions = $incomeTransactions->concat($expenseTransactions)
            ->concat($transferInTransactions)
            ->concat($transferOutTransactions)
            ->sortBy('date');
            
        $history = [];
        $runningBalance = $account->initial_balance ?? 0;
        
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $dayTransactions = $allTransactions->filter(function ($transaction) use ($currentDate) {
                return Carbon::parse($transaction->date)->format('Y-m-d') === $currentDate->format('Y-m-d');
            });
            
            foreach ($dayTransactions as $transaction) {
                if ($transaction->type === 'income') {
                    $runningBalance += $transaction->amount;
                } elseif ($transaction->type === 'expense') {
                    $runningBalance -= $transaction->amount;
                } elseif ($transaction->type === 'transfer') {
                    if ($transaction->transferTransaction->source_bank_account_id === $accountId) {
                        $runningBalance -= $transaction->amount;
                    } else {
                        $runningBalance += $transaction->amount;
                    }
                }
            }
            
            $history[] = [
                'date' => $dateKey,
                'balance' => $runningBalance
            ];
            
            $currentDate->addDay();
        }
        
        return $history;
    }
}