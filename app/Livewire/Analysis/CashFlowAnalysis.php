<?php

namespace App\Livewire\Analysis;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Attributes\On;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\DB;

/**
 * Nakit Akışı Analizi Bileşeni
 * 
 * Bu bileşen, nakit akışı analizini ve raporlamasını sağlar.
 * Özellikler:
 * - Tarih aralığına göre nakit akışı analizi
 * - Hesap bazlı filtreleme
 * - Gelir ve gider trendleri
 * - Nakit akışı sağlığı değerlendirmesi
 * - Detaylı metrikler (toplam gelir, gider, ortalama, zirve değerler)
 * - Görsel grafikler ve tablolar
 * 
 * @package App\Livewire\Analysis
 */
class CashFlowAnalysis extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var string Başlangıç tarihi (Y-m-d formatında) */
    public $startDate;

    /** @var string Bitiş tarihi (Y-m-d formatında) */
    public $endDate;

    /** @var string Analiz periyodu (daily, weekly, monthly, quarterly, yearly) */
    public $period = 'monthly';

    /** @var array Seçili hesap ID'leri */
    public $accountIds = [];
    
    /** @var string Grafik tipi (line, bar, stacked) */
    public $chartType = 'line';

    /** @var string Grafik başlangıç tarihi (Y-m-d formatında) */
    public $chartStartDate;

    /** @var string Grafik bitiş tarihi (Y-m-d formatında) */
    public $chartEndDate;

    /** @var string Grafik periyodu (daily, weekly, monthly, quarterly, yearly) */
    public $chartPeriod = 'monthly';

    /** @var array Grafik verileri */
    public $chartData = [];
    
    /** @var array Nakit akışı verileri */
    public $cashFlowData = [];

    /** @var float Net nakit akışı */
    public $netCashFlow = 0;

    /** @var float Toplam gelir */
    public $totalInflow = 0;

    /** @var float Toplam gider */
    public $totalOutflow = 0;

    /** @var float Ortalama gelir */
    public $averageInflow = 0;

    /** @var float Ortalama gider */
    public $averageOutflow = 0;

    /** @var float Kümülatif nakit akışı */
    public $cumulativeCashFlow = 0;

    /** @var float En yüksek gelir */
    public $peakInflow = 0;

    /** @var float En yüksek gider */
    public $peakOutflow = 0;

    /** @var array Nakit akışı özeti */
    public $cashFlowSummary = [];
    
    /** @var string|null Hata mesajı */
    public $errorMessage = null;

    /**
     * Bileşen başlatıldığında çalışır
     * Varsayılan tarih aralığını ve ilk verileri yükler
     */
    public function mount(): void
    {
        // Set initial default dates
        $this->resetDatesToDefault(); 
        $this->period = 'monthly'; 
        $this->chartPeriod = 'monthly';
        
        // Initial data load
        $this->loadData();
    }

    /**
     * Tüm tarihleri varsayılan değerlere sıfırlar (son 3 ay)
     */
    private function resetDatesToDefault(): void
    {
        $defaultStartDate = Carbon::now()->subMonths(3)->startOfDay()->format('Y-m-d');
        $defaultEndDate = Carbon::now()->format('Y-m-d');
        $this->startDate = $defaultStartDate;
        $this->endDate = $defaultEndDate;
        $this->chartStartDate = $defaultStartDate;
        $this->chartEndDate = $defaultEndDate;
        \Log::info('Dates reset to default:', ['start' => $this->startDate, 'end' => $this->endDate]);
    }
    
    /**
     * Tarih değerlerinin geçerliliğini kontrol eder
     * 
     * @return bool Tarihler geçerli ise true, değilse false
     */
    private function validateDates(): bool
    {
        $this->errorMessage = null; 
        try {
            $startDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);
            $now = Carbon::now();

            if ($startDate->isAfter($now)) {
                $this->errorMessage = 'Başlangıç tarihi gelecek bir tarih olamaz.';
                return false;
            }
            if ($endDate->isAfter($now)) {
                $this->errorMessage = 'Bitiş tarihi gelecek bir tarih olamaz.';
                return false;
            }
            if ($startDate->isAfter($endDate)) {
                $this->errorMessage = 'Başlangıç tarihi bitiş tarihinden sonra olamaz.';
                return false;
            }
            return true;
        } catch (\Exception $e) {
            $this->errorMessage = 'Geçersiz tarih formatı. Lütfen geçerli bir tarih girin.';
            return false;
        }
    }
    
    /**
     * Tüm verileri yükler ve hesaplar
     * Tarihlerin geçerliliğini kontrol eder ve gerekirse varsayılan değerlere sıfırlar
     */
    private function loadData(): void
    {
        // Validate dates and reset to default if invalid before loading data
        if (!$this->validateDates()) {
             $this->resetDatesToDefault();
             // Error message is already set by validateDates
        }
        
        \Log::info('Loading data with dates:', ['start' => $this->startDate, 'end' => $this->endDate, 'chartStart' => $this->chartStartDate, 'chartEnd' => $this->chartEndDate]);

        // Load data using the validated (or reset) dates
        $this->loadGeneralCashFlowData();
        $this->loadFilteredCashFlowData();
        $this->loadChartData();
    }
    
    /**
     * Genel nakit akışı verilerini yükler (hesap filtresi olmadan)
     */
    private function loadGeneralCashFlowData()
    {
        $this->generalCashFlowData = $this->getCashFlowData($this->startDate, $this->endDate, $this->period, []);
        
        // Genel nakit akışı metrikleri hesapla
        $this->generalNetCashFlow = $this->calculateNetCashFlow($this->generalCashFlowData);
        $this->generalAverageInflow = $this->calculateAverageInflow($this->generalCashFlowData);
        $this->generalAverageOutflow = $this->calculateAverageOutflow($this->generalCashFlowData);
        
        // Nakit akışı sağlığı ve özeti
        $this->cashFlowSummary = $this->generateCashFlowSummary($this->generalCashFlowData);
    }
    
    /**
     * Filtrelenmiş nakit akışı verilerini yükler (seçilen hesaplara göre)
     */
    private function loadFilteredCashFlowData()
    {
        $this->cashFlowData = $this->getCashFlowData($this->startDate, $this->endDate, $this->period, $this->accountIds);
        
        // Filtrelenmiş nakit akışı metrikleri hesapla
        $this->netCashFlow = $this->calculateNetCashFlow($this->cashFlowData);
        $this->totalInflow = $this->cashFlowData->sum('inflow');
        $this->totalOutflow = $this->cashFlowData->sum('outflow');
        $this->cumulativeCashFlow = $this->calculateCumulativeCashFlow($this->cashFlowData);
        $this->averageInflow = $this->calculateAverageInflow($this->cashFlowData);
        $this->averageOutflow = $this->calculateAverageOutflow($this->cashFlowData);
        $this->peakInflow = $this->calculatePeakInflow($this->cashFlowData);
        $this->peakOutflow = $this->calculatePeakOutflow($this->cashFlowData);
    }
    
    /**
     * Grafik verilerini yükler (ayrı tarih aralığı ve periyot kullanarak)
     */
    private function loadChartData()
    {
        // Ensure chart dates are initialized if somehow empty
        if (empty($this->chartStartDate) || empty($this->chartEndDate)) {
             $this->resetDatesToDefault();
        }
        
        $chartDataResult = $this->getCashFlowData($this->chartStartDate, $this->chartEndDate, $this->chartPeriod, $this->accountIds);
        
        $this->chartData = [
            'labels' => $chartDataResult->pluck('period')->toArray(),
            'inflowData' => $chartDataResult->pluck('inflow')->toArray(),
            'outflowData' => $chartDataResult->pluck('outflow')->toArray(),
            'netData' => $chartDataResult->pluck('net')->toArray(),
        ];
        \Log::info('Chart data loaded:', ['labels' => count($this->chartData['labels'])]);
    }
    
    /**
     * Filtre değişikliğinde grafiği günceller
     * 
     * @return null
     */
    #[On('filterChanged')]
    public function updateChart()
    {
        // Called by filter changes, reload data
        $this->loadData();
        
        \Log::info('Chart updated via filterChanged event', ['chartData' => $this->chartData]);
        
        // Dispatch event to update frontend chart
        $this->dispatch('cashFlowDataUpdated', [
            'chartType' => $this->chartType,
            'chartData' => $this->chartData
        ]);
        
        return null; 
    }
    
    /**
     * Başlangıç tarihi güncellendiğinde grafik başlangıç tarihini senkronize eder
     */
    public function updatedStartDate(): void
    {
        $this->chartStartDate = $this->startDate;
        $this->updatedChartStartDate(); 
    }

    /**
     * Bitiş tarihi güncellendiğinde grafik bitiş tarihini senkronize eder
     */
    public function updatedEndDate(): void
    {
       $this->chartEndDate = $this->endDate;
       $this->updatedChartEndDate();
    }

    /**
     * Hesap seçimleri güncellendiğinde grafiği günceller
     */
    public function updatedAccountIds()
    {
        $this->updateChart();
    }
    
    /**
     * Grafik başlangıç tarihi güncellendiğinde verileri yeniden yükler
     */
    public function updatedChartStartDate(): void
    {
        $this->errorMessage = null; 
        $isValid = true;

        try {
            $startDate = Carbon::parse($this->chartStartDate);
            $endDate = Carbon::parse($this->chartEndDate); 
            $now = Carbon::now();

            if ($startDate->isAfter($now)) {
                $this->errorMessage = 'Başlangıç tarihi gelecek bir tarih olamaz.';
                $isValid = false;
            } elseif ($startDate->isAfter($endDate)) {
                $this->errorMessage = 'Başlangıç tarihi bitiş tarihinden sonra olamaz.';
                $isValid = false;
            }

        } catch (\Exception $e) {
            $this->errorMessage = 'Geçersiz tarih formatı. Lütfen geçerli bir tarih girin.';
            $isValid = false;
        }

        if ($isValid) {
            $this->startDate = $this->chartStartDate;
        } else {
            $this->resetDatesToDefault();
        }

        $this->loadData(); 
        $this->dispatch('cashFlowDataUpdated', [
            'chartType' => $this->chartType,
            'chartData' => $this->chartData
        ]);
    }
    
    /**
     * Grafik bitiş tarihi güncellendiğinde verileri yeniden yükler
     */
    public function updatedChartEndDate(): void
    {
        $this->errorMessage = null;
        $isValid = true;

        try {
            $startDate = Carbon::parse($this->chartStartDate); 
            $endDate = Carbon::parse($this->chartEndDate);
            $now = Carbon::now();

            if ($endDate->isAfter($now)) {
                $this->errorMessage = 'Bitiş tarihi gelecek bir tarih olamaz.';
                $isValid = false;
            } elseif ($startDate->isAfter($endDate)) {
                $this->errorMessage = 'Başlangıç tarihi bitiş tarihinden sonra olamaz.';
                $isValid = false;
            }

        } catch (\Exception $e) {
            $this->errorMessage = 'Geçersiz tarih formatı. Lütfen geçerli bir tarih girin.';
            $isValid = false;
        }

        if ($isValid) {
            $this->endDate = $this->chartEndDate;
        } else {
            $this->resetDatesToDefault();
        }

        $this->loadData();
        $this->dispatch('cashFlowDataUpdated', [
            'chartType' => $this->chartType,
            'chartData' => $this->chartData
        ]);
    }
    
    /**
     * Grafik periyodu güncellendiğinde verileri yeniden yükler
     */
    public function updatedChartPeriod()
    {
        $this->loadChartData();
        $this->period = $this->chartPeriod;
        
        $this->cashFlowData = $this->getCashFlowData($this->startDate, $this->endDate, $this->period, $this->accountIds);
        
        $this->netCashFlow = $this->calculateNetCashFlow($this->cashFlowData);
        $this->cumulativeCashFlow = $this->calculateCumulativeCashFlow($this->cashFlowData);
        $this->averageInflow = $this->calculateAverageInflow($this->cashFlowData);
        $this->averageOutflow = $this->calculateAverageOutflow($this->cashFlowData);
        $this->peakInflow = $this->calculatePeakInflow($this->cashFlowData);
        $this->peakOutflow = $this->calculatePeakOutflow($this->cashFlowData);
        
        $this->dispatch('cashFlowDataUpdated', [
            'chartType' => $this->chartType,
            'chartData' => $this->chartData
        ]);
    }
    
    /**
     * Grafik filtrelerini varsayılan değerlere sıfırlar
     */
    public function resetChartFilters()
    {
        $this->chartStartDate = $this->startDate;
        $this->chartEndDate = $this->endDate;
        $this->chartPeriod = $this->period;
        
        $this->loadChartData();
        
        $this->dispatch('cashFlowDataUpdated', [
            'chartType' => $this->chartType,
            'chartData' => $this->chartData
        ]);
    }
    
    /**
     * Grafik tipi güncellendiğinde verileri yeniden yükler
     * 
     * @param string $value Yeni grafik tipi
     */
    public function updatedChartType($value)
    {
        $this->chartType = $value;
        $this->loadData();
        
        $this->dispatch('cashFlowDataUpdated', [
            'chartType' => $this->chartType,
            'chartData' => $this->chartData
        ]);
    }
    
    /**
     * Bileşenin görünümünü render eder
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.analysis.cash-flow', [
            'cashFlowData' => $this->cashFlowData,
            'accounts' => Account::where('user_id', auth()->id())->where('status', true)->get(),
            'netCashFlow' => $this->netCashFlow,
            'cumulativeCashFlow' => $this->cumulativeCashFlow,
            'totalInflow' => $this->totalInflow,
            'totalOutflow' => $this->totalOutflow,
            'averageInflow' => $this->averageInflow,
            'averageOutflow' => $this->averageOutflow,
            'peakInflow' => $this->peakInflow,
            'peakOutflow' => $this->peakOutflow,
            'cashFlowSummary' => $this->cashFlowSummary,
        ]);
    }
    
    /**
     * Form şemasını oluşturur
     * 
     * @return array Form bileşenleri
     */
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Filtreler')
                ->description('Nakit akışı analizini filtrelemek için aşağıdaki seçenekleri kullanın')
                ->collapsible()
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\DatePicker::make('startDate')
                            ->label('Başlangıç Tarihi')
                            ->displayFormat('d.m.Y')
                            ->native(false)
                            ->required(),
                        
                        Forms\Components\DatePicker::make('endDate')
                            ->label('Bitiş Tarihi')
                            ->displayFormat('d.m.Y')
                            ->native(false)
                            ->required(),
                    ]),
                    
                    Forms\Components\Grid::make(1)->schema([
                        Forms\Components\MultiSelect::make('accountIds')
                            ->label('Hesaplar')
                            ->searchable()
                            ->native(false)
                            ->preload()
                            ->options(function () {
                                return Account::where('user_id', auth()->id())
                                    ->where('status', true)
                                    ->where('type', '!=', 'cash') // Nakit hesapları filtrele
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }),
                    ]),
                ])
                ->columns(1),
        ];
    }
    
    /**
     * Nakit akışı verilerini getirir
     * 
     * @param string|null $startDate Başlangıç tarihi
     * @param string|null $endDate Bitiş tarihi
     * @param string|null $period Periyot (daily, weekly, monthly, quarterly, yearly)
     * @param array|null $accountIds Hesap ID'leri
     * @return \Illuminate\Support\Collection
     */
    public function getCashFlowData($startDate = null, $endDate = null, $period = null, $accountIds = null)
    {
        // Varsayılan değerleri kullan
        $startDate = $startDate ?? $this->startDate;
        $endDate = $endDate ?? $this->endDate;
        $period = $period ?? $this->period;
        $accountIds = $accountIds ?? $this->accountIds;
        
        $query = Transaction::query()
            ->where('user_id', auth()->id())
            ->whereBetween('date', [$startDate, $endDate])
            ->where(function ($query) {
                $query->where('type', 'income')
                    ->orWhere('type', 'expense');
            });
        
        if (!empty($accountIds)) {
            $query->where(function ($query) use ($accountIds) {
                $query->whereIn('source_account_id', $accountIds)
                    ->orWhereIn('destination_account_id', $accountIds);
            });
        }
        
        // Periyoda göre SQL sorgusunu oluşturalım
        if ($period === 'quarterly') {
            // Alt sorgu kullanarak çeyreklik sorguyu çözüyoruz
            $sql = "SELECT 
                    t.year,
                    t.quarter,
                    CONCAT(t.year, '-Q', t.quarter) as period,
                    SUM(t.income) as inflow,
                    SUM(t.expense) as outflow
                FROM (
                    SELECT 
                        YEAR(date) as year,
                        QUARTER(date) as quarter,
                        CASE WHEN type = 'income' THEN try_equivalent ELSE 0 END as income,
                        CASE WHEN type = 'expense' THEN try_equivalent ELSE 0 END as expense
                    FROM transactions
                    WHERE user_id = ? AND date BETWEEN ? AND ? AND (type = 'income' OR type = 'expense') AND deleted_at IS NULL
                ) as t
                GROUP BY t.year, t.quarter
                ORDER BY t.year, t.quarter";
            
            $bindings = [
                auth()->id(),
                $startDate,
                $endDate
            ];
            
            if (!empty($accountIds)) {
                // Hesap filtresi için ayrı bir sorgu kullanmamız gerekiyor
                $sql = "SELECT 
                        t.year,
                        t.quarter,
                        CONCAT(t.year, '-Q', t.quarter) as period,
                        SUM(t.income) as inflow,
                        SUM(t.expense) as outflow
                    FROM (
                        SELECT 
                            YEAR(date) as year,
                            QUARTER(date) as quarter,
                            CASE WHEN type = 'income' THEN try_equivalent ELSE 0 END as income,
                            CASE WHEN type = 'expense' THEN try_equivalent ELSE 0 END as expense
                        FROM transactions
                        WHERE user_id = ? AND date BETWEEN ? AND ? AND (type = 'income' OR type = 'expense') 
                        AND (source_account_id IN (" . implode(',', array_fill(0, count($accountIds), '?')) . ") 
                            OR destination_account_id IN (" . implode(',', array_fill(0, count($accountIds), '?')) . "))
                        AND deleted_at IS NULL
                    ) as t
                    GROUP BY t.year, t.quarter
                    ORDER BY t.year, t.quarter";
                
                $bindings = [
                    auth()->id(),
                    $startDate,
                    $endDate
                ];
                
                // Hesap ID'lerini iki kez ekliyoruz (source ve destination için)
                foreach ($accountIds as $id) {
                    $bindings[] = $id;
                }
                foreach ($accountIds as $id) {
                    $bindings[] = $id;
                }
            }
            
            $rawResults = DB::select($sql, $bindings);
            
            // Sonuçları collection'a çeviriyoruz
            $results = collect($rawResults)
                ->map(function ($item) {
                    $item->net = $item->inflow - $item->outflow;
                    return $item;
                });
        } else {
            // Diğer periyotlar için normal sorgu
            $dateFormat = $this->getDateFormat($period);
            
            $results = $query->select(
                    DB::raw("DATE_FORMAT(date, '{$dateFormat}') as period"),
                    DB::raw("SUM(CASE WHEN type = 'income' THEN try_equivalent ELSE 0 END) as inflow"),
                    DB::raw("SUM(CASE WHEN type = 'expense' THEN try_equivalent ELSE 0 END) as outflow")
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(function ($item) {
                    $item->net = $item->inflow - $item->outflow;
                    return $item;
                });
        }
        
        return $results;
    }
    
    /**
     * Periyoda göre tarih formatını döndürür
     * 
     * @param string|null $period Periyot (daily, weekly, monthly, quarterly, yearly)
     * @return string MySQL DATE_FORMAT için format string
     */
    private function getDateFormat($period = null)
    {
        $period = $period ?? $this->period;
        
        switch ($period) {
            case 'daily':
                return '%Y-%m-%d'; // Günlük: 2023-01-01
            case 'weekly':
                return '%x-W%v'; // Haftalık: 2023-W01
            case 'monthly':
                return '%Y-%m'; // Aylık: 2023-01
            case 'quarterly':
                return '%Y-Q' . DB::raw('QUARTER(date)'); // Çeyreklik: 2023-Q1
            case 'yearly':
                return '%Y'; // Yıllık: 2023
            default:
                return '%Y-%m'; // Varsayılan olarak aylık
        }
    }
    
    /**
     * Net nakit akışını hesaplar
     * 
     * @param \Illuminate\Support\Collection $data Nakit akışı verileri
     * @return float Net nakit akışı
     */
    private function calculateNetCashFlow($data)
    {
        $sum = $data->sum('net');
        \Log::info('Calculated Net Cash Flow: ' . $sum);
        return $sum;
    }
    
    /**
     * Kümülatif nakit akışını hesaplar
     * 
     * @param \Illuminate\Support\Collection $data Nakit akışı verileri
     * @return array Kümülatif nakit akışı verileri
     */
    private function calculateCumulativeCashFlow($data)
    {
        $cumulative = [];
        $runningTotal = 0;
        
        foreach ($data as $item) {
            $runningTotal += $item->net;
            $cumulative[] = [
                'period' => $item->period,
                'value' => $runningTotal
            ];
        }
        
        return $cumulative;
    }
    
    /**
     * Ortalama geliri hesaplar
     * 
     * @param \Illuminate\Support\Collection $data Nakit akışı verileri
     * @return float Ortalama gelir
     */
    private function calculateAverageInflow($data)
    {
        $totalInflow = $data->sum('inflow');
        
        if ($data->count() === 0) {
            return 0;
        }
        
        return $totalInflow / $data->count();
    }
    
    /**
     * Ortalama gideri hesaplar
     * 
     * @param \Illuminate\Support\Collection $data Nakit akışı verileri
     * @return float Ortalama gider
     */
    private function calculateAverageOutflow($data)
    {
        $totalOutflow = $data->sum('outflow');
        
        if ($data->count() === 0) {
            return 0;
        }
        
        return $totalOutflow / $data->count();
    }
    
    /**
     * En yüksek geliri hesaplar
     * 
     * @param \Illuminate\Support\Collection $data Nakit akışı verileri
     * @return float En yüksek gelir
     */
    private function calculatePeakInflow($data)
    {
        return $data->max('inflow');
    }
    
    /**
     * En yüksek gideri hesaplar
     * 
     * @param \Illuminate\Support\Collection $data Nakit akışı verileri
     * @return float En yüksek gider
     */
    private function calculatePeakOutflow($data)
    {
        return $data->max('outflow');
    }
    
    /**
     * Nakit akışı özetini oluşturur
     * 
     * @param \Illuminate\Support\Collection $data Nakit akışı verileri
     * @return array Nakit akışı özeti
     */
    private function generateCashFlowSummary($data)
    {
        $totalInflow = $data->sum('inflow');
        $totalOutflow = $data->sum('outflow');
        $netCashFlow = $totalInflow - $totalOutflow;
        
        // Nakit akışı oranı
        $cashFlowRatio = $totalOutflow > 0 ? $totalInflow / $totalOutflow : ($totalInflow > 0 ? 2 : 0);
        
        // Trend yüzdesini hesapla
        $trendPercent = 0;
        
        if ($data->count() >= 2) {
            $firstPeriod = $data->first();
            $lastPeriod = $data->last();
            
            if ($firstPeriod && $lastPeriod && $firstPeriod->net != 0) {
                $trendPercent = (($lastPeriod->net - $firstPeriod->net) / abs($firstPeriod->net)) * 100;
            }
        }
        
        $summary = [
            'totalInflow' => $totalInflow,
            'totalOutflow' => $totalOutflow,
            'netCashFlow' => $netCashFlow,
            'cashFlowRatio' => $cashFlowRatio,
            'trendPercent' => $trendPercent,
        ];
        
        // Nakit akışı sağlığı değerlendirmesi
        if ($netCashFlow > 0 && $cashFlowRatio >= 1.2) {
            $summary['health'] = 'excellent';
            $summary['healthMessage'] = 'Nakit akışı mükemmel durumda. Giderlerinizi karşılamak için yeterli gelir var ve tasarruf yapabiliyorsunuz.';
        } elseif ($netCashFlow > 0 && $cashFlowRatio >= 1.1) {
            $summary['health'] = 'good';
            $summary['healthMessage'] = 'Nakit akışı iyi durumda. Giderlerinizi karşılamak için yeterli gelir var.';
        } elseif ($netCashFlow >= 0) {
            $summary['health'] = 'adequate';
            $summary['healthMessage'] = 'Nakit akışı yeterli ancak iyileştirme fırsatları var. Giderlerinizi karşılayabiliyorsunuz ancak tasarruf için çok az pay kalıyor.';
        } elseif ($netCashFlow < 0 && $trendPercent > 0) {
            $summary['health'] = 'improving';
            $summary['healthMessage'] = 'Nakit akışı negatif ancak iyileşiyor. Trend olumlu yönde.';
        } elseif ($netCashFlow < 0 && $cashFlowRatio >= 0.8) {
            $summary['health'] = 'warning';
            $summary['healthMessage'] = 'Nakit akışı dikkat gerektiriyor. Giderleriniz gelirinizi aşıyor.';
        } else {
            $summary['health'] = 'critical';
            $summary['healthMessage'] = 'Nakit akışı kritik seviyede. Giderleriniz gelirinizi aşıyor. Acil önlem almanız gerekiyor.';
        }
        
        // Tavsiyeler
        $recommendations = [];
        
        if ($cashFlowRatio < 1.0) {
            $recommendations[] = 'Giderlerinizi azaltmak için bütçe planı oluşturun.';
            $recommendations[] = 'Gereksiz aboneliklerinizi ve tekrarlayan ödemelerinizi gözden geçirin.';
            $recommendations[] = 'Ek gelir kaynakları araştırın.';
        }
        
        if ($data->count() >= 3) {
            $lastThree = $data->take(-3);
            $trend = $lastThree->last()->net - $lastThree->first()->net;
            
            if ($trend < 0) {
                $recommendations[] = 'Son dönemlerde nakit akışınız azalıyor. Gelir kaynaklarınızı çeşitlendirmeyi düşünün.';
            }
        }
        
        $summary['recommendations'] = $recommendations;
        
        return $summary;
    }
}
