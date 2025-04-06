<?php

namespace App\Livewire\Customer;

use App\Models\Customer;
use App\Models\CustomerAgreement;
use App\Models\CustomerCredential;
use App\Models\CustomerNote;
use App\Services\Customer\Contracts\CustomerServiceInterface;
use App\DTOs\Customer\NoteData;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Notifications\Notification;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Exceptions\Halt;
use App\Models\User;

/**
 * Müşteri Detay Bileşeni
 * 
 * Bu bileşen, müşteri detaylarını görüntüleme ve müşteri notlarını yönetme işlevselliğini sağlar.
 * Özellikler:
 * - Müşteri bilgilerini görüntüleme
 * - Müşteri notları ekleme
 * - Not geçmişi görüntüleme
 * - Not türü yönetimi (not, arama, toplantı, e-posta, diğer)
 * - Müşteri bilgileri yönetimi (hassas bilgiler)
 * - Anlaşma yönetimi (tekrarlayan ödemeler)
 * 
 * @package App\Livewire\Customer
 */
final class CustomerDetail extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var Customer Müşteri modeli */
    public Customer $customer;

    /** @var bool Not ekleme modalının görünürlüğü */
    public $showNoteModal = false;

    /** @var bool Bilgi ekleme modalının görünürlüğü */
    public $showCredentialModal = false;

    /** @var bool Anlaşma ekleme modalının görünürlüğü */
    public $showAgreementModal = false;

    /** @var array Not verileri */
    public $data = [];

    /** @var array Bilgi verileri */
    public $credentialData = [
        'value' => []
    ];

    /** @var array Anlaşma verileri */
    public $agreementData = [];

    /** @var CustomerAgreement|null Düzenlenecek anlaşma */
    public ?CustomerAgreement $editingAgreement = null;

    /** @var CustomerCredential|null Düzenlenecek hassas bilgi */
    public ?CustomerCredential $editingCredential = null;

    /** @var CustomerNote|null Düzenlenecek not */
    public ?CustomerNote $editingNote = null;

    /** @var CustomerServiceInterface Müşteri servisi */
    private CustomerServiceInterface $customerService;

    /**
     * Bileşen başlatılırken müşteri servisini enjekte eder
     * 
     * @param CustomerServiceInterface $customerService Müşteri servisi
     * @return void
     */
    public function boot(CustomerServiceInterface $customerService): void
    {
        $this->customerService = $customerService;
    }

    /**
     * Bileşen mount edildiğinde müşteri verisini ayarlar
     * 
     * @param Customer $customer Müşteri modeli
     * @return void
     */
    public function mount(Customer $customer): void
    {
        $this->customer = $customer;
        $this->form->fill([
            'data' => [
                'type' => 'note',
                'content' => '',
                'activity_date' => now(),
                'assigned_user_id' => null,
            ],
        ]);
    }

    /**
     * Not ekleme modalını açar
     * 
     * @return void
     */
    public function addNote(): void
    {
        $this->editingNote = null;
        $this->data = [
            'type' => 'note',
            'content' => '',
            'activity_date' => now()->format('Y-m-d\TH:i'),
            'assigned_user_id' => null,
        ];
        $this->showNoteModal = true;
    }

    /**
     * Bilgi ekleme modalını açar
     * 
     * @return void
     */
    public function addCredential(): void
    {
        $this->editingCredential = null;
        $this->credentialData = ['value' => []];
        $this->showCredentialModal = true;
    }

    /**
     * Anlaşma ekleme modalını açar
     * 
     * @return void
     */
    public function addAgreement(): void
    {
        $this->editingAgreement = null;
        $this->agreementData = [
            'name' => '',
            'description' => '',
            'amount' => '',
            'start_date' => '',
            'next_payment_date' => '',
        ];
        $this->showAgreementModal = true;
    }

    /**
     * Hassas bilgi değeri ekler
     * 
     * @return void
     */
    public function addCredentialValue(): void
    {
        $this->credentialData['value'][] = '';
    }

    /**
     * Hassas bilgi değerini kaldırır
     * 
     * @param int $index Silinecek değerin indeksi
     * @return void
     */
    public function removeCredentialValue(int $index): void
    {
        unset($this->credentialData['value'][$index]);
        $this->credentialData['value'] = array_values($this->credentialData['value']);
    }

    /**
     * Bileşenin görünümünü render eder
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.customer.customer-detail', [
            'notes' => $this->customer->notes()
                ->with(['user', 'assignedUser'])
                ->latest('activity_date')
                ->get(),
            'credentials' => $this->customer->credentials()
                ->with('user')
                ->latest()
                ->get(),
            'agreements' => $this->customer->agreements()
                ->with('user')
                ->latest()
                ->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('data.type')
                    ->label('Not Türü')
                    ->options([
                        'note' => 'Not',
                        'call' => 'Telefon Görüşmesi',
                        'meeting' => 'Toplantı',
                        'email' => 'E-posta',
                        'other' => 'Diğer',
                    ])
                    ->required(),
                DateTimePicker::make('data.activity_date')
                    ->label('Aktivite Tarihi')
                    ->required(),
                Select::make('data.assigned_user_id')
                    ->label('Atanan Kişi')
                    ->options(User::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Textarea::make('data.content')
                    ->label('İçerik')
                    ->required()
                    ->minLength(3)
                    ->maxLength(1000),
            ])
            ->statePath('data');
    }

    public function saveNote(): void
    {
        $this->validate([
            'data.type' => ['required', 'in:note,call,meeting,email,other'],
            'data.content' => ['required', 'string', 'min:3', 'max:1000'],
            'data.activity_date' => ['required', 'date'],
            'data.assigned_user_id' => ['nullable', 'exists:users,id'],
        ], [
            'data.type.required' => 'Not türü seçilmelidir.',
            'data.type.in' => 'Geçersiz not türü seçildi.',
            'data.content.required' => 'Not içeriği boş bırakılamaz.',
            'data.content.min' => 'Not içeriği en az 3 karakter olmalıdır.',
            'data.content.max' => 'Not içeriği en fazla 1000 karakter olabilir.',
            'data.activity_date.required' => 'Aktivite tarihi seçilmelidir.',
            'data.activity_date.date' => 'Geçerli bir tarih seçilmelidir.',
            'data.assigned_user_id.exists' => 'Seçilen kullanıcı bulunamadı.',
        ]);

        try {
            $noteData = NoteData::fromArray([
                'type' => $this->data['type'],
                'content' => $this->data['content'],
                'activity_date' => $this->data['activity_date'],
                'user_id' => auth()->id(),
                'customer_id' => $this->customer->id,
                'assigned_user_id' => $this->data['assigned_user_id'] ?? null,
            ]);

            if ($this->editingNote) {
                $this->editingNote->update($noteData->toArray());
                $message = 'Not güncellendi';
            } else {
                $this->customerService->addNote($this->customer, $noteData);
                $message = 'Not eklendi';
            }

            $this->showNoteModal = false;
            $this->data = [];
            $this->editingNote = null;

            Notification::make()
                ->title($message)
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata oluştu')
                ->body('Not kaydedilirken bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Hassas bilgi kaydeder
     * 
     * @return void
     */
    public function saveCredential(): void
    {
        $this->validate([
            'credentialData.name' => ['required', 'string'],
            'credentialData.value' => ['required', 'array'],
            'credentialData.value.*' => ['required', 'string'],
        ], [
            'credentialData.name.required' => 'Bilgi adı boş bırakılamaz.',
            'credentialData.value.required' => 'En az bir değer girilmelidir.',
            'credentialData.value.*.required' => 'Tüm değerler doldurulmalıdır.',
        ]);

        try {
            $data = [
                'user_id' => auth()->id(),
                'customer_id' => $this->customer->id,
                'name' => $this->credentialData['name'],
                'value' => array_values(array_filter($this->credentialData['value'])),
                'status' => true,
            ];

            if ($this->editingCredential) {
                $this->editingCredential->update($data);
                $message = 'Bilgi güncellendi';
            } else {
                CustomerCredential::create($data);
                $message = 'Bilgi eklendi';
            }

            $this->showCredentialModal = false;
            $this->credentialData = ['value' => []];
            $this->editingCredential = null;

            Notification::make()
                ->title($message)
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata')
                ->body('Bilgi kaydedilirken bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Yeni anlaşma ekler
     * 
     * @return void
     */
    public function saveAgreement(): void
    {
        $this->validate([
            'agreementData.name' => ['required', 'string'],
            'agreementData.description' => ['nullable', 'string'],
            'agreementData.amount' => ['required', 'numeric', 'min:0'],
            'agreementData.start_date' => ['required', 'date'],
            'agreementData.next_payment_date' => ['required', 'date', 'after:agreementData.start_date'],
        ], [
            'agreementData.name.required' => 'Anlaşma adı boş bırakılamaz.',
            'agreementData.amount.required' => 'Tutar boş bırakılamaz.',
            'agreementData.amount.numeric' => 'Tutar sayısal bir değer olmalıdır.',
            'agreementData.amount.min' => 'Tutar 0\'dan küçük olamaz.',
            'agreementData.start_date.required' => 'Başlangıç tarihi seçilmelidir.',
            'agreementData.start_date.date' => 'Geçerli bir başlangıç tarihi seçilmelidir.',
            'agreementData.next_payment_date.required' => 'Sonraki ödeme tarihi seçilmelidir.',
            'agreementData.next_payment_date.date' => 'Geçerli bir sonraki ödeme tarihi seçilmelidir.',
            'agreementData.next_payment_date.after' => 'Sonraki ödeme tarihi başlangıç tarihinden sonra olmalıdır.',
        ]);

        try {
            $data = [
                'user_id' => auth()->id(),
                'customer_id' => $this->customer->id,
                'name' => $this->agreementData['name'],
                'description' => $this->agreementData['description'] ?? null,
                'amount' => $this->agreementData['amount'],
                'start_date' => $this->agreementData['start_date'],
                'next_payment_date' => $this->agreementData['next_payment_date'],
                'status' => $this->agreementData['status'] ?? 'active',
            ];

            if ($this->editingAgreement) {
                $this->editingAgreement->update($data);
            } else {
                CustomerAgreement::create($data);
            }

            $this->showAgreementModal = false;
            $this->agreementData = [];
            $this->editingAgreement = null;

            Notification::make()
                ->title($this->editingAgreement ? 'Anlaşma güncellendi' : 'Anlaşma eklendi')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata')
                ->body('Anlaşma kaydedilirken bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Anlaşma siler
     * 
     * @param int $id Silinecek anlaşmanın ID'si
     * @return void
     */
    public function deleteAgreement(int $id): void
    {
        try {
            $agreement = CustomerAgreement::findOrFail($id);
            $agreement->delete();

            Notification::make()
                ->title('Anlaşma silindi')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata')
                ->body('Anlaşma silinirken bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Hassas bilgi siler
     * 
     * @param int $id Silinecek bilginin ID'si
     * @return void
     */
    public function deleteCredential(int $id): void
    {
        try {
            $credential = CustomerCredential::findOrFail($id);
            $credential->delete();

            Notification::make()
                ->title('Bilgi silindi')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata')
                ->body('Bilgi silinirken bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Anlaşma düzenleme modalını açar
     * 
     * @param int $id Düzenlenecek anlaşmanın ID'si
     * @return void
     */
    public function editAgreement(int $id): void
    {
        $this->editingAgreement = CustomerAgreement::findOrFail($id);
        $this->agreementData = [
            'name' => $this->editingAgreement->name,
            'description' => $this->editingAgreement->description,
            'amount' => $this->editingAgreement->amount,
            'start_date' => $this->editingAgreement->start_date->format('Y-m-d'),
            'next_payment_date' => $this->editingAgreement->next_payment_date->format('Y-m-d'),
            'status' => $this->editingAgreement->status,
        ];
        $this->showAgreementModal = true;
    }

    /**
     * Hassas bilgi düzenleme modalını açar
     * 
     * @param int $id Düzenlenecek hassas bilginin ID'si
     * @return void
     */
    public function editCredential(int $id): void
    {
        $this->editingCredential = CustomerCredential::findOrFail($id);
        $this->credentialData = [
            'name' => $this->editingCredential->name,
            'value' => is_array($this->editingCredential->value) ? $this->editingCredential->value : [$this->editingCredential->value],
        ];
        $this->showCredentialModal = true;
    }

    /**
     * Not düzenleme modalını açar
     * 
     * @param int $id Düzenlenecek notun ID'si
     * @return void
     */
    public function editNote(int $id): void
    {
        $this->editingNote = CustomerNote::findOrFail($id);
        $this->data = [
            'type' => $this->editingNote->type,
            'content' => $this->editingNote->content,
            'activity_date' => $this->editingNote->activity_date->format('Y-m-d\TH:i'),
            'assigned_user_id' => $this->editingNote->assigned_user_id,
        ];
        $this->showNoteModal = true;
    }
} 