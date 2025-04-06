<?php

namespace App\Livewire\Analysis;

use App\Models\Transaction;
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
 * Kategori Analizi Bileşeni
 * 
 * Bu bileşen, gelir ve gider kategorilerinin detaylı analizini sağlar.
 * Özellikler:
 * - Tarih aralığına göre kategori bazlı analiz
 * - Gelir ve gider kategorileri için ayrı analiz
 * - Kategori bazlı büyüme ve trend analizi
 * - En çok işlem yapılan kategorilerin tespiti
 * - Kategori bazlı ortalama işlem tutarları
 * 
 * @package App\Livewire\Analysis
 */
class CategoryAnalysis extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var string Başlangıç tarihi (Y-m-d formatında) */
    public $startDate;

    /** @var string Bitiş tarihi (Y-m-d formatında) */
    public $endDate;

    /** @var string Analiz periyodu (monthly) */
    public $period = 'monthly';

    /** @var array Seçili kategori ID'leri */
    public $selectedCategories = [];

    /** @var string Grafik tipi (bar) */
    public $chartType = 'bar';

    /** @var string Analiz tipi (income/expense) */
    public $analysisType = 'income';
    
    /** @var array Kategori analiz verileri */
    public $categoryData = [];

    /** @var array En çok işlem yapılan kategoriler */
    public $topCategories = [];

    /** @var float Toplam işlem tutarı */
    public $totalAmount = 0;

    /** @var float Ortalama işlem tutarı */
    public $averageAmount = 0;

    /** @var array Kategori büyüme oranları */
    public $categoryGrowth = [];

    /** @var array Kategori trendleri */
    public $categoryTrends = [];
    
    /** @var string|null Hata mesajı */
    public $errorMessage = null;

    /**
     * Bileşen başlatıldığında çalışır
     * Varsayılan tarih aralığını ve ilk verileri yükler
     */
    public function mount(): void
    {
        // Varsayılan olarak son 3 ay
        $this->startDate = Carbon::now()->subMonths(3)->startOfDay()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
        
        // İlk yüklemede verileri hesapla
        $this->loadData();
    }

    /**
     * Tarih değerlerinin geçerliliğini kontrol eder
     * 
     * @return bool Tarihler geçerli ise true, değilse false
     */
    private function validateDates()
    {
        $this->errorMessage = null;
        
        try {
            $startDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);
            $now = Carbon::now();
            
            if ($startDate->isAfter($now)) {
                $this->errorMessage = 'Başlangıç tarihi gelecek bir tarih olamaz.';
                $this->startDate = Carbon::now()->subMonths(3)->startOfDay()->format('Y-m-d');
                return false;
            }
            
            if ($endDate->isAfter($now)) {
                $this->errorMessage = 'Bitiş tarihi gelecek bir tarih olamaz.';
                $this->endDate = Carbon::now()->format('Y-m-d');
                return false;
            }
            
            if ($startDate->isAfter($endDate)) {
                $this->errorMessage = 'Başlangıç tarihi bitiş tarihinden sonra olamaz.';
                $this->startDate = $endDate->copy()->subMonths(3)->format('Y-m-d');
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            $this->errorMessage = 'Geçersiz tarih formatı. Lütfen geçerli bir tarih girin.';
            $this->startDate = Carbon::now()->subMonths(3)->startOfDay()->format('Y-m-d');
            $this->endDate = Carbon::now()->format('Y-m-d');
            return false;
        }
    }

    /**
     * Tüm analiz verilerini yükler
     */
    private function loadData()
    {
        if (!$this->validateDates()) {
            return;
        }

        $this->loadCategoryData();
        $this->calculateMetrics();
        $this->analyzeTrends();
    }

    /**
     * Kategori bazlı işlem verilerini yükler
     */
    private function loadCategoryData()
    {
        $query = Transaction::query()
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereBetween('transactions.date', [$this->startDate, $this->endDate]);

        if ($this->analysisType === 'income') {
            $query->where('categories.type', 'income');
        } else {
            $query->where('categories.type', 'expense');
        }

        if (!empty($this->selectedCategories)) {
            $query->whereIn('category_id', $this->selectedCategories);
        }

        $this->categoryData = $query
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(try_equivalent) as total_amount'),
                DB::raw('AVG(try_equivalent) as average_amount')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_amount')
            ->get();
    }

    /**
     * Metrikleri hesaplar (toplam tutar, ortalama tutar, en çok işlem yapılan kategoriler)
     */
    private function calculateMetrics()
    {
        $this->totalAmount = $this->categoryData->sum('total_amount');
        $this->averageAmount = $this->categoryData->avg('average_amount');
        
        // En çok işlem yapılan kategoriler
        $this->topCategories = $this->categoryData
            ->sortByDesc('total_amount')
            ->take(5)
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'total_amount' => $category->total_amount,
                    'transaction_count' => $category->transaction_count,
                    'average_amount' => $category->average_amount,
                    'percentage' => ($category->total_amount / $this->totalAmount) * 100
                ];
            });
    }

    /**
     * Kategori trendlerini analiz eder
     */
    private function analyzeTrends()
    {
        // Kategorilerin büyüme trendini analiz et
        foreach ($this->categoryData as $category) {
            $previousPeriodData = Transaction::query()
                ->join('categories', 'transactions.category_id', '=', 'categories.id')
                ->where('category_id', $category->id)
                ->whereBetween('transactions.date', [
                    Carbon::parse($this->startDate)->subMonths(3),
                    Carbon::parse($this->startDate)->subDay()
                ])
                ->select(DB::raw('SUM(try_equivalent) as total_amount'))
                ->first();

            $previousAmount = $previousPeriodData->total_amount ?? 0;
            $currentAmount = $category->total_amount;

            $growth = $previousAmount > 0 
                ? (($currentAmount - $previousAmount) / $previousAmount) * 100 
                : ($currentAmount > 0 ? 100 : 0);

            $this->categoryGrowth[$category->id] = [
                'percentage' => round($growth, 2),
                'trend' => $growth > 0 ? 'up' : ($growth < 0 ? 'down' : 'stable')
            ];
        }
    }

    /**
     * Başlangıç tarihi güncellendiğinde verileri yeniden yükler
     */
    public function updatedStartDate()
    {
        $this->loadData();
    }

    /**
     * Bitiş tarihi güncellendiğinde verileri yeniden yükler
     */
    public function updatedEndDate()
    {
        $this->loadData();
    }

    /**
     * Seçili kategoriler güncellendiğinde verileri yeniden yükler
     */
    public function updatedSelectedCategories()
    {
        $this->loadData();
    }

    /**
     * Analiz tipi güncellendiğinde seçili kategorileri sıfırlar ve verileri yeniden yükler
     */
    public function updatedAnalysisType()
    {
        $this->selectedCategories = [];
        $this->loadData();
    }

    /**
     * Bileşenin görünümünü render eder
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.analysis.category-analysis', [
            'categories' => Category::where('type', $this->analysisType)
                ->orderBy('name')
                ->get()
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
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\DatePicker::make('startDate')
                        ->label('Başlangıç Tarihi')
                        ->displayFormat('d.m.Y')
                        ->native(false)
                        ->required()
                        ->live(),
                    Forms\Components\DatePicker::make('endDate')
                        ->label('Bitiş Tarihi')
                        ->displayFormat('d.m.Y')
                        ->native(false)
                        ->required()
                        ->live(),
                ]),
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\Select::make('analysisType')
                        ->label('Analiz Tipi')
                        ->options([
                            'income' => 'Gelir Kategorileri',
                            'expense' => 'Gider Kategorileri'
                        ])
                        ->required()
                        ->live()
                        ->native(false),
                    Forms\Components\Select::make('selectedCategories')
                        ->label('Kategoriler')
                        ->multiple()
                        ->native(false)
                        ->options(function () {
                            return Category::where('type', $this->analysisType)
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->placeholder('Tüm kategoriler')
                        ->live()
                ]),
        ];
    }
} 