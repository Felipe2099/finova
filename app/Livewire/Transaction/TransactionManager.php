<?php

namespace App\Livewire\Transaction;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Services\Transaction\Implementations\TransactionService;
use App\DTOs\Transaction\TransactionData;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use App\Livewire\Transaction\Widgets\TransactionStatsWidget;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

/**
 * Transaction Management Component
 * 
 * Manages financial transactions.
 * Features:
 * - List transactions
 * - Create transactions
 * - Edit transactions
 * - Delete transactions
 * - Filter transactions (type, category, date range)
 * - Bulk actions
 * - Statistics widgets
 * 
 * @package App\Livewire\Transaction
 */
class TransactionManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /** @var TransactionService Transaction service */
    private TransactionService $transactionService;
    
    /** @var array Listener events */
    protected $listeners = ['refreshTransactions' => '$refresh'];

    /** Active filter */
    public $activeFilter = 'income';

    protected $queryString = [
        'activeFilter' => ['except' => 'all', 'as' => 'filter'],
    ];

    /**
     * Inject the transaction service on component boot.
     * 
     * @param TransactionService $transactionService Transaction service
     * @return void
     */
    public function boot(TransactionService $transactionService): void
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Lifecycle: runs when component is mounted.
     * 
     * @param int|null $transactionId Transaction ID to edit
     * @return void
     */
    public function mount($transactionId = null): void
    {
        if ($transactionId) {
            $this->transaction = Transaction::findOrFail($transactionId);

            if ($this->transaction->is_taxable && $this->transaction->tax_rate) {
                $taxRate = $this->transaction->tax_rate / 100;
                $netAmount = $this->transaction->amount / (1 + $taxRate);
                $this->transaction->tax_amount = round($this->transaction->amount - $netAmount, 2);
            }

            if ($this->transaction->currency === 'TRY') {
                $this->transaction->exchange_rate = 1;
            }

            $this->form->fill($this->transaction->toArray());
        }
    }

    /**
     * Set the active filter.
     *
     * @param string $filter Selected filter ('all', 'income', 'expense', 'transfer', 'payments')
     * @return void
     */
    public function setFilter(string $filter): void
    {
        $this->activeFilter = $filter;
    }

    /**
     * Livewire lifecycle hook that runs when the $activeFilter property is updated.
     *
     * Resets table pagination to ensure the user starts from the first page
     * after changing the filter.
     *
     * @param string $value The new value of $activeFilter
     * @return void
     */
    public function updatedActiveFilter(string $value): void
    {
        $this->resetPage(); // Restore pagination reset
    }

    /**
     * Build the table configuration.
     * 
     * @param Tables\Table $table Table instance
     * @return Tables\Table Configured table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        // Define the base query without the active filter applied here
        $baseQuery = Transaction::query()
            ->with(['category', 'sourceAccount', 'destinationAccount'])
            ->orderByDesc('id'); // Add default sorting by ID in descending order

        return $table
            ->query($baseQuery) // Use the base query
            ->modifyQueryUsing(fn (Builder $query) => $this->applyActiveFilter($query)) // Apply filter dynamically
            ->defaultSort('id', 'desc') // Add default sort to table configuration
            ->emptyStateHeading('İşlem Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir işlem ekleyin.')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tarih')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->default(function (Transaction $record) {
                        if (in_array($record->type, ['atm_deposit', 'atm_withdraw'])) {
                            return 'Bankamatik İşlemleri';
                        }
                        if ($record->type === 'loan_payment') {
                            return 'Kredi İşlemleri';
                        }
                        if ($record->type === 'payment') {
                            return 'Kredi Kartı İşlemleri';
                        }
                        return '-';
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Açıklama')
                    ->default(function (Transaction $record) {
                        return $record->description ?? '-';
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Tutar')
                    ->formatStateUsing(function (Transaction $record) {
                        if ($record->currency === 'TRY') {
                            return '₺' . number_format($record->amount, 2, ',', '.');
                        }
                        return match($record->currency) {
                            'USD' => '$',
                            'EUR' => '€',
                            'GBP' => '£',
                            default => $record->currency
                        } . number_format($record->amount, 2, ',', '.') . ' - ' . 
                        '<span class="text-blue-600">₺' . number_format($record->try_equivalent, 2, ',', '.') . '</span>';
                    })
                    ->html()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tür')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                        'atm_deposit' => 'info',
                        'atm_withdraw' => 'info',
                        'transfer' => 'warning',
                        'payment' => 'info',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'income' => 'Gelir',
                        'expense' => 'Gider',
                        'transfer' => 'Transfer',
                        'atm_deposit' => 'ATM Para Yatırma',
                        'atm_withdraw' => 'ATM Para Çekme',
                        'loan_payment' => 'Kredi Ödemesi',
                        'payment' => 'Kredi Kartı Ödemesi',
                        'debt_payment' => 'Borç Ödemesi',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tür')
                    ->options([
                        Transaction::TYPE_INCOME => 'Gelir',
                        Transaction::TYPE_EXPENSE => 'Gider',
                        Transaction::TYPE_TRANSFER => 'Transfer',
                        Transaction::TYPE_LOAN_PAYMENT => 'Kredi Ödemesi',
                        Transaction::TYPE_CREDIT_PAYMENT => 'Kredi Kartı Ödemesi',
                    ])
                    ->native(false),
                
                Tables\Filters\SelectFilter::make('income_category_id')
                    ->label('Gelir Kategorisi')
                    ->options(function() {
                        return cache()->remember("all_categories_income", now()->addHours(24), function () {
                            return Category::where('type', 'income')
                                ->where('status', true)
                                ->pluck('name', 'id')
                                ->toArray();
                        });
                    })
                    ->query(function ($query, $data) {
                        if (!$data['value']) {
                            return $query;
                        }
                        
                        return $query->where(function ($q) use ($data) {
                            $q->where('category_id', $data['value'])
                              ->where('type', 'income');
                        });
                    })
                    ->native(false),
                
                Tables\Filters\SelectFilter::make('expense_category_id')
                    ->label('Gider Kategorisi')
                    ->options(function() {
                        return cache()->remember("all_categories_expense", now()->addHours(24), function () {
                            return Category::where('type', 'expense')
                                ->where('status', true)
                                ->pluck('name', 'id')
                                ->toArray();
                        });
                    })
                    ->query(function ($query, $data) {
                        if (!$data['value']) {
                            return $query;
                        }
                        
                        return $query->where(function ($q) use ($data) {
                            $q->where('category_id', $data['value'])
                              ->where('type', 'expense');
                        });
                    })
                    ->native(false),
                
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('Başlangıç Tarihi')
                            ->displayFormat('d.m.Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('Bitiş Tarihi')
                            ->displayFormat('d.m.Y')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        
                        if ($data['from_date'] ?? null) {
                            $indicators['from_date'] = 'Başlangıç: ' . \Carbon\Carbon::parse($data['from_date'])->format('d.m.Y');
                        }
                        
                        if ($data['to_date'] ?? null) {
                            $indicators['to_date'] = 'Bitiş: ' . \Carbon\Carbon::parse($data['to_date'])->format('d.m.Y');
                        }
                        
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Düzenle')
                    ->url(fn (Transaction $record): string => route('admin.transactions.edit', $record))
                    ->extraAttributes(['wire:navigate' => true])
                    ->icon('heroicon-m-pencil-square')
                    ->visible(fn (Transaction $record) => !in_array($record->type, ['transfer', 'atm_deposit', 'atm_withdraw', 'loan_payment', 'payment']) && auth()->user()->can('transactions.edit')),

                Tables\Actions\DeleteAction::make()
                    ->modalHeading('İşlemi Sil')
                    ->modalDescription('Bu işlemi silmek istediğinize emin misiniz?')
                    ->modalSubmitActionLabel('Sil')
                    ->modalCancelActionLabel('İptal')
                    ->using(function (Transaction $record) {
                        if (in_array($record->type, ['transfer', 'atm_deposit', 'atm_withdraw', 'loan_payment', 'payment'])) {
                            Notification::make()
                                ->title('İşlem Silinemez')
                                ->body('Bu tür işlemler (' . $this->getTransactionTypeName($record->type) . ') silinemez.')
                                ->danger()
                                ->send();
                            return false;
                        }

                        try {
                            $result = $this->transactionService->delete($record);
                            if ($result) {
                                $this->dispatch('transactionDeleted');
                            }
                            return $result;
                        } catch (\Exception $e) {
                            Log::error('İşlem silme hatası: ' . $e->getMessage());
                            Notification::make()
                                ->title('Hata')
                                ->body('İşlem silinirken bir hata oluştu: ' . $e->getMessage())
                                ->danger()
                                ->send();
                            return false;
                        }
                    })
                    ->visible(fn (Transaction $record) => !in_array($record->type, ['transfer', 'atm_deposit', 'atm_withdraw', 'loan_payment', 'payment']) && auth()->user()->can('transactions.delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            $nonDeletableTypes = ['transfer', 'atm_deposit', 'atm_withdraw', 'loan_payment', 'payment'];
                            $nonDeletableRecords = $records->filter(fn ($record) => in_array($record->type, $nonDeletableTypes));
                            
                            if ($nonDeletableRecords->count() > 0) {
                                $types = $nonDeletableRecords->pluck('type')->unique()->map(fn ($type) => $this->getTransactionTypeName($type))->implode(', ');
                                Notification::make()
                                    ->title('Bazı İşlemler Silinemez')
                                    ->body("Seçilen işlemler arasında silinemeyen türler ($types) bulunmaktadır. Lütfen standart işlemleri seçin.")
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            $successCount = 0;
                            $errorCount = 0;
                            
                            foreach ($records as $record) {
                                try {
                                    if ($this->transactionService->delete($record)) {
                                        $successCount++;
                                    } else {
                                        $errorCount++;
                                    }
                                } catch (\Exception $e) {
                                    $errorCount++;
                                }
                            }
                            
                            if ($successCount > 0) {
                                $this->dispatch('transactionDeleted');
                                Notification::make()
                                    ->title($successCount . ' İşlem Başarıyla Silindi')
                                    ->success()
                                    ->send();
                            }
                            
                            if ($errorCount > 0) {
                                Notification::make()
                                    ->title($errorCount . ' İşlem Silinemedi')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(auth()->user()->can('transactions.delete')),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label('İşlem Oluştur')
                    ->url(route('admin.transactions.create'))
                    ->extraAttributes(['wire:navigate' => true])
                    ->visible(auth()->user()->can('transactions.create')),
            ]);
    }

    /**
     * Applies the currently active filter to the table query.
     *
     * @param Builder $query The Eloquent query builder instance.
     * @return Builder The modified query builder instance.
     */
    protected function applyActiveFilter(Builder $query): Builder
    {
        if ($this->activeFilter === 'all') {
            return $query; // No filter needed for 'all'
        }

        return match ($this->activeFilter) {
            'income' => $query->where('type', Transaction::TYPE_INCOME),
            'expense' => $query->where('type', Transaction::TYPE_EXPENSE),
            'transfer' => $query->where('type', Transaction::TYPE_TRANSFER)->where('amount', '>', 0),
            'payments' => $query->whereIn('type', [Transaction::TYPE_LOAN_PAYMENT, Transaction::TYPE_DEBT_PAYMENT, Transaction::TYPE_CREDIT_PAYMENT]),
            'atm' => $query->whereIn('type', [Transaction::TYPE_ATM_DEPOSIT, Transaction::TYPE_ATM_WITHDRAW]),
            default => $query, // Should not happen, but safety first
        };
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        // Calculate filter counts
        $baseCountQuery = Transaction::query();

        $filterCounts = [
            'all' => (clone $baseCountQuery)->where(function (Builder $query) {
                            $query->where('type', '<>', 'transfer')
                                  ->orWhere(function (Builder $q) {
                                      $q->where('type', 'transfer')
                                        ->where('amount', '>', 0);
                                  });
                        })->count(),
            'income' => (clone $baseCountQuery)->where('type', Transaction::TYPE_INCOME)->count(),
            'expense' => (clone $baseCountQuery)->where('type', Transaction::TYPE_EXPENSE)->count(),
            'transfer' => (clone $baseCountQuery)->where('type', Transaction::TYPE_TRANSFER)->where('amount', '>', 0)->count(),
            'payments' => (clone $baseCountQuery)->whereIn('type', [Transaction::TYPE_LOAN_PAYMENT, Transaction::TYPE_DEBT_PAYMENT, Transaction::TYPE_CREDIT_PAYMENT])->count(),
            'atm' => (clone $baseCountQuery)->whereIn('type', [Transaction::TYPE_ATM_DEPOSIT, Transaction::TYPE_ATM_WITHDRAW])->count(),
        ];

        return view('livewire.transaction.transaction-manager', [
            'stats' => new TransactionStatsWidget(),
            'filterCounts' => $filterCounts, // Pass counts to the view
        ]);
    }

    /**
     * Returns the transaction type name
     * 
     * @param string $type Transaction type
     * @return string Transaction type name
     */
    private function getTransactionTypeName(string $type): string
    {
        return match ($type) {
            'income' => 'Gelir',
            'expense' => 'Gider',
            'transfer' => 'Transfer',
            'atm_deposit' => 'ATM Para Yatırma',
            'atm_withdraw' => 'ATM Para Çekme',
            'loan_payment' => 'Kredi Ödemesi',
            'payment' => 'Kredi Kartı İşlemleri',
            'debt_payment' => 'Borç Ödemesi',
            default => $type,
        };
    }
}