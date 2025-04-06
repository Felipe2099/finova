<?php

namespace App\Livewire\Proposal;

use App\Models\ProposalTemplate;
use App\Models\ProposalItem;
use App\Models\Customer;
use Filament\Forms;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;

/**
 * Teklif Şablonu Form Bileşeni
 * 
 * Bu bileşen, teklif şablonlarının oluşturulması ve düzenlenmesi için form sağlar.
 * Özellikler:
 * - Teklif şablonu oluşturma
 * - Teklif şablonu düzenleme
 * - Teklif kalemleri yönetimi
 * - Müşteri seçimi
 * - Geçerlilik tarihi belirleme
 * - Ödeme koşulları tanımlama
 * - Notlar ekleme
 * 
 * @package App\Livewire\Proposal
 */
class ProposalTemplateForm extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var ProposalTemplate|null Düzenlenen teklif şablonu */
    public ?ProposalTemplate $record = null;

    /** @var array Form verileri */
    public array $data = [];

    /** @var bool Düzenleme modu durumu */
    public bool $isEdit = false;

    /**
     * Form yapılandırmasını oluşturur
     * 
     * @param Forms\Form $form Form nesnesi
     * @return Forms\Form Yapılandırılmış form
     */
    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->model($this->record ?? new ProposalTemplate())
            ->statePath('data');
    }

    /**
     * Bileşen başlatılırken çalışır
     * 
     * @param int|null $id Düzenlenecek teklif şablonu ID'si
     * @return void
     */
    public function mount(?int $id = null): void
    {
        $this->isEdit = (bool) $id;

        if ($this->isEdit) {
            $this->record = ProposalTemplate::with(['customer', 'items'])->findOrFail($id);
            $this->data = $this->record->toArray();
            
            // Items verilerini form'a ekle
            $this->data['items'] = $this->record->items->map(function ($item) {
                return [
                    'name' => $item->name,
                    'description' => $item->description,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                ];
            })->toArray();
        }

        $this->form->fill($this->data);
    }

    /**
     * Form şemasını oluşturur
     * 
     * @return array Form bileşenleri
     */
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Teklif Bilgileri')
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->label('Müşteri')
                        ->options(Customer::query()->pluck('name', 'id'))
                        ->native(false)
                        ->searchable()
                        ->placeholder('Müşteri seçiniz')
                        ->required(),
                    Forms\Components\TextInput::make('title')
                        ->label('Teklif Başlığı')
                        ->required()
                        ->maxLength(255)
                        ->default(fn () => 'Teklif ' . now()->format('d.m.Y')),
                    Forms\Components\RichEditor::make('content')
                        ->label('Teklif İçeriği')
                        ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList'])
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Teklif Kalemleri')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->label('Kalemler')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Kalem Adı')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('description')
                                ->label('Açıklama')
                                ->rows(3)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('price')
                                ->label('Birim Fiyat')
                                ->required()
                                ->numeric()
                                ->prefix('₺'),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Miktar')
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->minValue(1),
                            Forms\Components\Select::make('unit')
                                ->label('Birim')
                                ->options([
                                    'piece' => 'Adet',
                                    'hour' => 'Saat',
                                    'day' => 'Gün',
                                    'month' => 'Ay',
                                    'year' => 'Yıl',
                                    'package' => 'Paket',
                                ])
                                ->required()
                                ->native(false)
                                ->default('piece'),
                        ])
                        ->columns(3)
                        ->defaultItems(1)
                        ->addActionLabel('Kalem Ekle')
                        ->reorderable()
                        ->collapsible()
                        ->cloneable()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Teklif Detayları')
                ->schema([
                    Forms\Components\DatePicker::make('valid_until')
                        ->label('Geçerlilik Tarihi')
                        ->required()
                        ->minDate(now())
                        ->default(now()->addDays(30)),
                    Forms\Components\Select::make('status')
                        ->label('Durum')
                        ->options([
                            'draft' => 'Taslak',
                            'sent' => 'Gönderildi',
                            'accepted' => 'Kabul Edildi',
                            'rejected' => 'Reddedildi',
                            'expired' => 'Süresi Doldu',
                        ])
                        ->native(false)
                        ->default('draft')
                        ->required(),
                    Forms\Components\RichEditor::make('payment_terms')
                        ->label('Ödeme Koşulları')
                        ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList'])
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notlar')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    /**
     * Form verilerini kaydeder
     * 
     * @throws \Exception Kayıt sırasında oluşabilecek hatalar
     * @return void
     */
    public function save(): void
    {
        try {
            $data = $this->form->getState();
            
            // Items verilerini ayır
            $items = $data['items'] ?? [];
            unset($data['items']);
            
            \DB::beginTransaction();
            
            try {
                if ($this->isEdit) {
                    $this->record->update($data);
                    $proposal = $this->record;
                } else {
                    $proposal = ProposalTemplate::create($data);
                }

                // Teklif kalemlerini kaydet
                if (!empty($items)) {
                    // Mevcut kalemleri sil
                    if ($this->isEdit) {
                        $proposal->items()->delete();
                    }
                    
                    // Yeni kalemleri ekle
                    foreach ($items as $item) {
                        $proposal->items()->create([
                            'name' => $item['name'],
                            'description' => $item['description'] ?? null,
                            'price' => $item['price'],
                            'quantity' => $item['quantity'],
                            'unit' => $item['unit'],
                        ]);
                    }
                }

                \DB::commit();

                Notification::make()
                    ->success()
                    ->title($this->isEdit ? 'Teklif şablonu güncellendi' : 'Teklif şablonu oluşturuldu')
                    ->duration(5000)
                    ->send();

                $this->redirectRoute('admin.proposals.templates');
                
            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Hata!')
                ->body($e->getMessage())
                ->duration(5000)
                ->send();
        }
    }

    /**
     * Form işlemini iptal eder
     * 
     * @return void
     */
    public function cancel(): void
    {
        $this->redirect(route('admin.proposals.templates'), navigate: true);
    }

    /**
     * Bileşenin görünümünü render eder
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.proposal.form');
    }
}
