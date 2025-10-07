<?php

namespace App\Livewire\Lead;

use App\Models\Lead;
use App\Models\User;
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
use App\Models\CustomerGroup;
use App\Models\Customer;
use App\Services\Lead\Contracts\LeadServiceInterface;
use App\DTOs\Lead\LeadData;

/**
 * Lead Manager Component
 * 
 * This component provides functionality to manage leads.
 * Features:
 * - Lead list view
 * - New lead creation
 * - Lead editing
 * - Lead deletion
 * - Lead conversion to customer
 * - Status tracking
 * - Source and status filtering
 * 
 * @package App\Livewire\Lead
 */
final class LeadManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /** @var LeadServiceInterface Lead service */
    private LeadServiceInterface $leadService;

    /**
     * When the component is booted, the lead service is injected
     * 
     * @param LeadServiceInterface $leadService Lead service
     * @return void
     */
    public function boot(LeadServiceInterface $leadService): void 
    {
        $this->leadService = $leadService;
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
            ->query(Lead::query())
            ->defaultGroup(
                Tables\Grouping\Group::make('status')
                    ->label('Durum')
                    ->getTitleFromRecordUsing(fn (Lead $record): string => match ($record->status) {
                        'new' => 'Yeni',
                        'contacted' => 'İletişime Geçildi',
                        'proposal_sent' => 'Teklif Gönderildi',
                        'negotiating' => 'Görüşülüyor',
                        'converted' => 'Müşteriye Çevrildi',
                        'lost' => 'Kaybedildi',
                        default => ucfirst($record->status),
                    })
            )
            ->emptyStateHeading('Potansiyel Müşteri Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir potansiyel müşteri oluşturun.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Müşteri Adı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-posta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('next_contact_date')
                    ->label('Sonraki Görüşme Tarihi')
                    ->dateTime('d/m/Y h:i')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'new' => 'Yeni',
                        'contacted' => 'İletişime Geçildi',
                        'proposal_sent' => 'Teklif Gönderildi',
                        'negotiating' => 'Görüşülüyor',
                        'converted' => 'Müşteriye Çevrildi',
                        'lost' => 'Kaybedildi',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'gray',
                        'contacted' => 'info',
                        'proposal_sent' => 'warning',
                        'negotiating' => 'primary',
                        'converted' => 'success',
                        'lost' => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->label('Kaynak')
                    ->options([
                        'website' => 'Web Sitesi',
                        'referral' => 'Referans',
                        'social_media' => 'Sosyal Medya',
                        'other' => 'Diğer',
                    ])
                    ->native(false)
                    ->placeholder('Tüm Kaynaklar'),
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'new' => 'Yeni',
                        'contacted' => 'İletişime Geçildi',
                        'proposal_sent' => 'Teklif Gönderildi',
                        'negotiating' => 'Görüşülüyor',
                        'converted' => 'Müşteriye Çevrildi',
                        'lost' => 'Kaybedildi',
                    ])
                    ->native(false)
                    ->placeholder('Tüm Durumlar'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Düzenle')
                    ->visible(fn () => auth()->user()->can('leads.edit'))
                    ->hidden(fn (Lead $record): bool => $record->status === 'converted')
                    ->modalHeading('Potansiyel Müşteriyi Düzenle')
                    ->modalSubmitActionLabel('Güncelle')
                    ->modalCancelActionLabel('İptal')
                    ->form($this->getLeadForm())
                    ->successNotificationTitle('Potansiyel müşteri düzenlendi'),
                Tables\Actions\Action::make('convert')
                    ->label('Müşteriye Çevir')
                    ->visible(fn () => auth()->user()->can('leads.convert_customer'))
                    ->hidden(fn (Lead $record): bool => $record->status === 'converted')
                    ->icon('heroicon-m-user-plus')
                    ->modalWidth('4xl')
                    ->color('success')
                    ->form([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Müşteri Türü')
                                    ->options([
                                        'corporate' => 'Kurumsal',
                                        'individual' => 'Bireysel',
                                    ])
                                    ->default(fn (Lead $record): string => $record->type)
                                    ->required()
                                    ->reactive()
                                    ->native(false)
                                    ->columnSpan(12),
                            ])->columns(12),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('tax_number')
                                    ->label(fn (callable $get) => $get('type') === 'corporate' ? 'Vergi No' : 'TC No')
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
                                    ->columnSpan(fn (callable $get) => $get('type') === 'individual' ? 12 : 6),

                                Forms\Components\TextInput::make('tax_office')
                                    ->label('Vergi Dairesi')
                                    ->required(fn (callable $get) => $get('type') === 'corporate')
                                    ->visible(fn (callable $get) => $get('type') === 'corporate')
                                    ->columnSpan(6),
                            ])->columns(12),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Select::make('customer_group_id')
                                    ->label('Müşteri Grubu')
                                    ->options(CustomerGroup::where('status', true)->pluck('name', 'id'))
                                    ->native(false)
                                    ->columnSpan(12),
                            ])->columns(12),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Textarea::make('conversion_reason')
                                    ->label('Dönüşüm Nedeni')
                                    ->rows(2)
                                    ->maxLength(1000)
                                    ->columnSpan(12),
                            ])->columns(12),
                    ])
                    ->action(function (array $data, Lead $record): void {
                        $this->leadService->convertToCustomer($record, $data);
                    })
                    ->modalHeading('Müşteriye Çevir')
                    ->modalSubmitActionLabel('Çevir'),
                Tables\Actions\DeleteAction::make()
                    ->label('Sil')
                    ->visible(fn () => auth()->user()->can('leads.delete'))
                    ->modalHeading('Potansiyel Müşteriyi Sil')
                    ->modalDescription('Bu potansiyel müşteriyi silmek istediğinize emin misiniz?')
                    ->modalSubmitActionLabel('Sil')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Potansiyel müşteri silindi'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Potansiyel Müşteri Oluştur')
                    ->visible(fn () => auth()->user()->can('leads.create'))
                    ->modalHeading('Yeni Potansiyel Müşteri')
                    ->modalSubmitActionLabel('Kaydet')
                    ->modalCancelActionLabel('İptal')
                    ->createAnother(false)
                    ->successNotificationTitle('Potansiyel müşteri oluşturuldu')
                    ->using(function (array $data): Lead {
                        $leadData = LeadData::fromArray([
                            ...$data,
                            'user_id' => auth()->id(),
                            'status' => 'new',
                        ]);
                        return $this->leadService->create($leadData);
                    })
                    ->form($this->getLeadForm()),
            ]);
    }

    /**
     * Creates the lead form
     * 
     * @return array Form components
     */
    protected function getLeadForm(): array
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
                        ->label('Müşteri Türü')
                        ->options([
                            'corporate' => 'Kurumsal',
                            'individual' => 'Bireysel',
                        ])
                        ->default('corporate')
                        ->required()
                        ->native(false)
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
                    Forms\Components\Select::make('source')
                        ->label('Kaynak')
                        ->options([
                            'website' => 'Web Sitesi',
                            'referral' => 'Referans',
                            'social_media' => 'Sosyal Medya',
                            'other' => 'Diğer',
                        ])
                        ->default('other')
                        ->required()
                        ->native(false),
                ])->columns(3),

            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Durum')
                        ->options([
                            'new' => 'Yeni',
                            'contacted' => 'İletişime Geçildi',
                            'proposal_sent' => 'Teklif Gönderildi',
                            'negotiating' => 'Görüşülüyor',
                            'lost' => 'Kaybedildi',
                        ])
                        ->default('new')
                        ->required()
                        ->native(false),
                    Forms\Components\DateTimePicker::make('next_contact_date')
                        ->label('Sonraki İletişim Tarihi')
                        ->seconds(false)
                        ->native(false),
                ])->columns(2),

            Forms\Components\Textarea::make('address')
                ->label('Adres')
                ->rows(2)
                ->maxLength(1000),

            Forms\Components\Textarea::make('notes')
                ->label('Notlar')
                ->rows(2)
                ->maxLength(1000),
        ];
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        abort_unless(auth()->user()->can('leads.view'), 403);
        return view('livewire.lead.lead-manager');
    }
} 