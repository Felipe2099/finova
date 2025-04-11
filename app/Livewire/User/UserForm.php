<?php

namespace App\Livewire\User;

use App\DTOs\User\UserData;
use App\Models\User;
use App\Services\User\Contracts\UserServiceInterface;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Kullanıcı oluşturma ve düzenleme formu bileşeni.
 * 
 * Bu bileşen, kullanıcı bilgilerinin oluşturulması ve düzenlenmesi için
 * Filament Form API kullanarak bir form arayüzü sağlar. Kullanıcının temel
 * bilgileri, rol atamaları ve komisyon ayarları bu form üzerinden yönetilir.
 */
class UserForm extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var User|null Düzenlenen kullanıcı */
    public ?User $user = null;
    
    /** @var bool Düzenleme modu */
    public bool $isEdit = false;
    
    /** @var array Kullanıcı rolleri */
    public array $roles = [];
    
    /** @var bool Komisyon kullanımı */
    public bool $hasCommission = false;
    
    /** @var float|null Komisyon oranı */
    public ?float $commissionRate = null;
    
    /** @var array Form verileri */
    public $data = [];
    
    /** @var User|null Restore edilecek silinen kullanıcı */
    public ?User $deletedUser = null;

    /** @var bool Geri yükleme modalını göster/gizle */
    public bool $showRestoreModal = false;

    /**
     * İzinleri bir modal içinde görüntüler
     */
    public $showPermissionsModal = false;
    public $selectedRoleId = null;
    public $permissionsList = [];

    /**
     * Formu hazırlar ve varsa mevcut kullanıcı verilerini yükler
     */
    public function mount($user = null): void
    {
        // Düzenleme modu kontrolü
        $this->isEdit = $user !== null;
        
        // Düzenleme ise kullanıcı ve rollerini yükle
        if ($this->isEdit) {
            $this->user = User::with('roles')->find($user->id);
        } else {
            $this->user = new User();
        }
        
        // Formları ayrı render et
        if ($this->isEdit) {
            $this->createEditForm();
        } else {
            $this->createNewForm();
        }
    }
    
    /**
     * Düzenleme formu oluşturur (şifre OLMADAN)
     */
    private function createEditForm(): void
    {
        // Kullanıcı bilgilerini ve rol ID'sini al
        $role = $this->user->roles->first();
        $roleId = $role ? $role->id : null;

        // Formu doldurmak için verileri hazırla
        $formData = [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'status' => $this->user->status,
            'roles' => $roleId,
            'has_commission' => $this->user->has_commission ?? false,
            'commission_rate' => $this->user->commission_rate,
        ];

        // Formu hazır verilerle doldur
        $this->form->fill($formData);
    }
    
    /**
     * Yeni kullanıcı formu oluşturur (şifre İLE)
     */
    private function createNewForm(): void
    {
        $this->form->fill([
            'name' => '',
            'email' => '',
            'phone' => '',
            'password' => '', // Sadece yeni kullanıcı formunda şifre var
            'status' => 1,
            'roles' => null,
            'has_commission' => false,
            'commission_rate' => null,
        ]);
    }

    /**
     * Form şemasını tanımlar
     */
    public function form(Form $form): Form
    {
        // Temel alanlar - her iki formda da ortak
        $schema = [
            Section::make('Kullanıcı Bilgileri')
                ->columns(2)
                ->schema($this->getUserFieldsSchema()),
            
            Section::make('Durum ve Roller')
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->label('Durum')
                        ->options([
                            1 => 'Aktif',
                            0 => 'Pasif',
                        ])
                        ->native(false)
                        ->required()
                        ->default(1),
                    
                    
                    Select::make('roles')
                        ->label('Rol')
                        ->preload()
                        ->options(Role::pluck('name', 'id'))
                        ->required()
                        ->native(false)
                        ->reactive(),
                    
                    Placeholder::make('permissions_info')
                        ->label('İzinler')
                        ->content(function (Get $get) {
                            $roleId = $get('roles');
                            if (empty($roleId)) {
                                return 'Rol seçilmedi.';
                            }
                            
                            $role = Role::find($roleId);
                            if (!$role || $role->permissions->isEmpty()) {
                                return 'Bu role ait izin bulunmuyor.';
                            }
                            
                            return view('livewire.user.partials.permissions-button', [
                                'roleId' => $roleId,
                                'count' => $role->permissions->count(),
                            ]);
                        })
                        ->columnSpanFull(),
                ]),
            
            Section::make('Komisyon Bilgileri')
                ->columns(2)
                ->schema([
                    Toggle::make('has_commission')
                        ->label('Komisyon Kullanıcısı')
                        ->reactive()
                        ->default(false),
                    
                    TextInput::make('commission_rate')
                        ->label('Komisyon Oranı (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->visible(fn (callable $get) => $get('has_commission')),
                ]),
        ];
        
        return $form->schema($schema)->statePath('data');
    }
    
    /**
     * Kullanıcı bilgileri alanlarını döndürür
     * Edit ve Create moduna göre farklı alanlar içerir
     */
    private function getUserFieldsSchema(): array
    {
        // Temel alanlar (her iki formda da var)
        $fields = [
            TextInput::make('name')
                ->label('Adı Soyadı')
                ->required()
                ->maxLength(255),
            
            TextInput::make('email')
                ->label('E-posta Adresi')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(
                    table: User::class, 
                    column: 'email', 
                    ignorable: $this->user,
                    modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at')
                ),
            
            TextInput::make('phone')
                ->label('Telefon')
                ->tel()
                ->maxLength(255),
        ];
        
        // Sadece create modunda şifre alanı ekle
        if (!$this->isEdit) {
            $fields[] = TextInput::make('password')
                ->label('Şifre')
                ->password()
                ->required()
                ->rule(Password::default());
        }
        
        return $fields;
    }
    
    /**
     * Form verilerini kaydeder
     */
    public function save(): void
    {
        try {
            $data = $this->form->getState();
            
            // Rol verisini doğru formatta hazırla
            $roleId = $data['roles'] ?? null;
            
            // Rolü doğru şekilde al - isimlerini kullan
            $roles = [];
            if ($roleId) {
                // Role ID'sini kullanarak rol nesnesini bul
                $role = Role::find($roleId);
                if ($role) {
                    $roles = [$role->name]; // İsim kullan - Spatie\Permission isim bekliyor
                }
            }
            
            // Komisyon oranını ayarla - eğer komisyon kullanıcısı değilse 0 olmalı
            $commissionRate = $data['has_commission'] ? ($data['commission_rate'] ?? 0) : 0;
            
            $userService = app(UserServiceInterface::class);
            
            // Yeni kayıtlarda, aynı email ile silinmiş kullanıcı var mı kontrol et
            if (!$this->isEdit) {
                // Aynı email ile silinmiş kullanıcıyı bul
                $this->deletedUser = User::onlyTrashed()->where('email', $data['email'])->first();

                if ($this->deletedUser) {
                    // Silinmiş kullanıcı varsa, modalı göster ve işlemi durdur
                    $this->showRestoreModal = true;
                    return;
                }
                
                // Silinmiş kullanıcı yoksa, yeni kullanıcı oluştur
                $userData = new UserData(
                    name: $data['name'],
                    email: $data['email'],
                    phone: $data['phone'],
                    password: $data['password'], // Create modunda şifre var
                    status: $data['status'],
                    has_commission: $data['has_commission'],
                    commission_rate: $commissionRate,
                    roles: $roles
                );
                
                $userService->create($userData);
                
                Notification::make('user-created')
                    ->title('Kullanıcı oluşturuldu')
                    ->success()
                    ->send();
            } else {
                // Mevcut kullanıcıyı güncelle
                $userData = new UserData(
                    name: $data['name'],
                    email: $data['email'],
                    phone: $data['phone'], 
                    password: null, // Edit modunda şifre yok
                    status: $data['status'],
                    has_commission: $data['has_commission'],
                    commission_rate: $commissionRate,
                    roles: $roles
                );
                
                $userService->update($this->user, $userData);
                
                Notification::make('user-updated')
                    ->title('Kullanıcı güncellendi')
                    ->success()
                    ->send();
            }
            
            $this->redirectRoute('admin.users.index', navigate: true);
        } catch (\Exception $e) {
            Notification::make('user-operation-error')
                ->title('Hata!')
                ->body('İşlem sırasında bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    /**
     * Kullanıcı geri yükleme işlemini onaylar ve gerçekleştirir.
     */
    public function confirmRestore(): void
    {
        if (!$this->deletedUser) {
            return; // Geri yüklenecek kullanıcı yoksa çık
        }

        try {
            $data = $this->form->getState(); // Formdaki güncel verileri al

            // Rol verisini doğru formatta hazırla
            $roleId = $data['roles'] ?? null;
            $roles = [];
            if ($roleId) {
                $role = Role::find($roleId);
                if ($role) {
                    $roles = [$role->name];
                }
            }

            // Komisyon oranını ayarla
            $commissionRate = $data['has_commission'] ? ($data['commission_rate'] ?? 0) : 0;

            $userService = app(UserServiceInterface::class);

            // 1. Kullanıcıyı geri yükle (Bildirim göndermeden)
            $userService->restore($this->deletedUser, false);

            // 2. Kullanıcıyı formdaki yeni bilgilerle güncelle
            $userData = new UserData(
                name: $data['name'],
                email: $data['email'],
                phone: $data['phone'],
                password: null, // Geri yüklerken şifre güncellenmez (istersen eklenebilir)
                status: $data['status'],
                has_commission: $data['has_commission'],
                commission_rate: $commissionRate,
                roles: $roles
            );
             // ÖNEMLİ: $this->deletedUser referansı restore'dan sonra değişebilir, ID ile tekrar bulalım
            $restoredUser = User::find($this->deletedUser->id);
            if ($restoredUser) {
                 $userService->update($restoredUser, $userData);
            } else {
                 // Hata durumu - kullanıcı bulunamadı
                 throw new \Exception("Geri yüklenen kullanıcı bulunamadı.");
            }


            Notification::make('user-restored')
                ->title('Kullanıcı geri yüklendi ve güncellendi')
                ->success()
                ->send();

            $this->showRestoreModal = false; // Modalı kapat
            $this->redirectRoute('admin.users.index', navigate: true); // Yönlendir

        } catch (\Exception $e) {
            Notification::make('user-restore-error')
                ->title('Geri Yükleme Hatası!')
                ->body('Kullanıcı geri yüklenirken/güncellenirken bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
             $this->showRestoreModal = false; // Hata durumunda da modalı kapat
        }
    }

    /**
     * Kullanıcı geri yükleme işlemini iptal eder ve modalı kapatır.
     */
    public function cancelRestore(): void
    {
        $this->showRestoreModal = false;
        $this->deletedUser = null; // İptal edildiğinde referansı temizle
    }

    /**
     * İptal et ve kullanıcı listesine dön
     */
    public function cancel(): void
    {
        $this->redirectRoute('admin.users.index', navigate: true);
    }

    /**
     * İzinleri bir modal içinde görüntüler
     */
    public function showPermissions($roleId): void
    {
        $this->selectedRoleId = $roleId;
        
        $role = Role::with('permissions')->find($roleId);
        if ($role) {
            // İzinleri Türkçe display_name ile hazırla
            $this->permissionsList = $role->permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name ?? $permission->name, // Doğrudan display_name kullan
                ];
            })->sortBy('display_name')->values()->toArray();
        }
        
        $this->showPermissionsModal = true;
    }

    public function closePermissionsModal(): void
    {
        $this->showPermissionsModal = false;
    }

    /**
     * Bileşenin görünümünü render eder
     */
    public function render()
    {
        return view('livewire.user.user-form');
    }
} 