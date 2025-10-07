<?php

namespace App\Livewire\Customer;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Services\Customer\Contracts\CustomerServiceInterface;
use App\DTOs\Customer\CustomerData;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;

/**
 * Customer Manager Component
 * 
 * This component provides functionality to manage customers.
 * Features:
 * - Customer list view
 * - Create new customer
 * - Edit customer
 * - Delete customer
 * - Customer detail view
 * - Customer filtering (group, type)
 * - Customer status management
 * 
 * @package App\Livewire\Customer
 */
final class CustomerManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /** @var CustomerServiceInterface Customer service */
    private CustomerServiceInterface $customerService;

    /**
     * When the component is booted, the customer service is injected
     * 
     * @param CustomerServiceInterface $customerService Customer service
     * @return void
     */
    public function boot(CustomerServiceInterface $customerService): void 
    {
        $this->customerService = $customerService;
    }

    /**
     * Creates the table configuration
     * 
     * @param Tables\Table $table Table object
     * @return Tables\Table Configured table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(Customer::query())
            ->emptyStateHeading('Müşteri Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir müşteri oluşturun.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Müşteri Adı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Müşteri Türü')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'individual' => 'Bireysel',
                        'corporate' => 'Kurumsal',
                    }),
                Tables\Columns\TextColumn::make('group.name')
                    ->label('Grup'),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-posta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('Durum')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('customer_group_id')
                    ->label('Müşteri Grubu')
                    ->options(CustomerGroup::where('status', true)->pluck('name', 'id'))
                    ->placeholder('Tüm Gruplar')
                    ->native(false),
                SelectFilter::make('type')
                    ->label('Müşteri Türü')
                    ->options([
                        'individual' => 'Bireysel',
                        'corporate' => 'Kurumsal',
                    ])
                    ->placeholder('Tüm Tipler')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Müşteri Bilgileri')
                    ->label('Detay')
                    ->url(fn (Customer $record) => route('admin.customers.show', $record))
                    ->extraAttributes(['wire:navigate' => true])
                    ->visible(fn () => auth()->user()->can('customers.detail')),
                Tables\Actions\EditAction::make()
                    ->modalHeading('Müşteriyi Düzenle')
                    ->modalSubmitActionLabel('Güncelle')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Müşteri düzenlendi')
                    ->label('Düzenle')
                    ->visible(fn () => auth()->user()->can('customers.edit'))
                    ->form($this->getCustomerForm())
                    ->using(function (Customer $record, array $data): Customer {
                        $customerData = CustomerData::fromArray([
                            ...$data,
                            'user_id' => auth()->id(),
                        ]);
                        return $this->customerService->update($record, $customerData);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Müşteriyi Sil')
                    ->modalDescription('Bu müşteriyi silmek istediğinize emin misiniz?')
                    ->modalSubmitActionLabel('Sil')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Müşteri silindi')
                    ->visible(fn () => auth()->user()->can('customers.delete'))
                    ->using(function (Customer $record): void {
                        $this->customerService->delete($record, true);
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Müşteri Oluştur')
                    ->modalHeading('Yeni Müşteri')
                    ->modalSubmitActionLabel('Kaydet')
                    ->modalCancelActionLabel('İptal')
                    ->createAnother(false)
                    ->successNotificationTitle('Müşteri oluşturuldu')
                    ->visible(fn () => auth()->user()->can('customers.create'))
                    ->form($this->getCustomerForm())
                    ->using(function (array $data): Customer {
                        $customerData = CustomerData::fromArray([
                            ...$data,
                            'user_id' => auth()->id(),
                        ]);
                        return $this->customerService->create($customerData);
                    }),
            ]);
    }

    /**
     * Creates the customer form
     * 
     * @return array Form components
     */
    protected function getCustomerForm(): array
    {
        return [
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Müşteri Adı')
                        ->required()
                        ->minLength(2)
                        ->maxLength(255)
                        ->columnSpan(1),
                    Forms\Components\Select::make('type')
                        ->label('Müşteri Tipi')
                        ->options([
                            'corporate' => 'Kurumsal',
                            'individual' => 'Bireysel',
                        ])
                        ->default('corporate')
                        ->required()
                        ->reactive()
                        ->native(false)
                        ->columnSpan(1),
                ])->columns(2),

            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('tax_number')
                        ->label('Vergi/TC No')
                        ->required(fn (callable $get) => $get('type') === 'corporate')
                        ->numeric()
                        ->rules([
                            fn (callable $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                if ($get('type') === 'corporate' && strlen($value) !== 10) {
                                    $fail('Vergi numarası 10 haneli olmalıdır.');
                                } elseif ($get('type') === 'individual' && strlen($value) !== 11) {
                                    $fail('TC kimlik numarası 11 haneli olmalıdır.');
                                }
                            },
                        ])
                        ->placeholder(fn (callable $get) => 
                            $get('type') === 'corporate' ? '1234567890' : '12345678901'
                        )
                        ->helperText(fn (callable $get) => 
                            $get('type') === 'corporate' ? 
                                'Vergi numarası 10 haneli olmalıdır' : 
                                'TC kimlik numarası 11 haneli olmalıdır'
                        )
                        ->columnSpan(fn (callable $get) => $get('type') === 'individual' ? 2 : 1),
                    Forms\Components\TextInput::make('tax_office')
                        ->label('Vergi Dairesi')
                        ->required(fn (callable $get) => $get('type') === 'corporate')
                        ->visible(fn (callable $get) => $get('type') === 'corporate')
                        ->minLength(2)
                        ->maxLength(255)
                        ->columnSpan(1),
                ])->columns(2),

            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('email')
                        ->label('E-posta')
                        ->email()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label('Telefon')
                        ->tel()
                        ->numeric()
                        ->minLength(10)
                        ->maxLength(11)
                        ->placeholder('05555555555'),
                ])->columns(2),

            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('city')
                        ->label('İl')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('district')
                        ->label('İlçe')
                        ->maxLength(255),
                    Forms\Components\Select::make('customer_group_id')
                        ->label('Müşteri Grubu')
                        ->options(CustomerGroup::where('status', true)->pluck('name', 'id'))
                        ->native(false)
                        ->placeholder('Grup Seçin'),
                ])->columns(3),

            Forms\Components\Textarea::make('address')
                ->label('Adres')
                ->rows(2)
                ->maxLength(1000),

            Forms\Components\Textarea::make('description')
                ->label('Açıklama')
                ->rows(2)
                ->maxLength(1000),

            Forms\Components\Toggle::make('status')
                ->label('Durum')
                ->default(true),
        ];
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.customer.customer-manager');
    }
} 