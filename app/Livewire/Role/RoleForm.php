<?php

namespace App\Livewire\Role;

use App\DTOs\Role\RoleData;
use App\Services\Role\Contracts\RoleServiceInterface;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms;
use Filament\Notifications\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\CheckboxList;

class RoleForm extends Component implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public ?Role $role = null;

    public bool $isEdit = false;

    public $formData = [];

    private RoleServiceInterface $roleService;

    public function boot(RoleServiceInterface $roleService): void
    {
        $this->roleService = $roleService;
    }

    public function mount(Role $role = null): void
    {
        $this->isEdit = $role !== null;

        if ($this->isEdit) {
            $this->role = Role::with('permissions')->findOrFail($role->id);
            $this->createEditForm();
        } else {
            $this->role = new Role();
            $this->createNewForm();
        }
    }

    private function createEditForm(): void
    {
        $rolePermissions = $this->role->permissions->pluck('name')->toArray();
        $this->formData = [
            'name' => $this->role->name,
        ];

        $groups = $this->getPermissionGroups();

        // Tüm izin gruplarını eksiksiz eklemek için döngüyü kontrol edelim
        foreach ($groups as $groupName => $groupData) {
            if (is_array($groupData) && !isset($groupData['name'])) {
                // Alt grupları olan kategori
                foreach ($groupData as $subGroupName => $permissions) {
                    if (!empty($permissions) && is_array($permissions)) {
                        $key = "permissions_{$groupName}_{$subGroupName}";
                        $groupPermissions = array_keys($permissions);
                        $selectedInGroup = array_intersect($rolePermissions, $groupPermissions);
                        $this->formData[$key] = array_values($selectedInGroup); // CheckboxList için dizi
                    } else {
                        $this->formData["permissions_{$groupName}_{$subGroupName}"] = [];
                    }
                }
            } else {
                // Tek seviyeli kategori
                $key = "permissions_{$groupName}";
                $permissionsInGroup = is_array($groupData) ? $groupData : [];

                if (!empty($permissionsInGroup)) {
                    $groupPermissionNames = array_keys($permissionsInGroup);
                    $selectedInGroup = array_intersect($rolePermissions, $groupPermissionNames);
                    $this->formData[$key] = array_values($selectedInGroup); // CheckboxList için dizi
                } else {
                    $this->formData[$key] = [];
                }
            }
        }

        $this->form->fill($this->formData);
    }

    private function createNewForm(): void
    {
        $this->formData = ['name' => ''];
        $groups = $this->getPermissionGroups();

        foreach ($groups as $groupName => $groupData) {
            if (is_array($groupData) && !isset($groupData['name'])) {
                foreach ($groupData as $subGroupName => $permissions) {
                    $key = "permissions_{$groupName}_{$subGroupName}";
                    $this->formData[$key] = [];
                }
            } else {
                $key = "permissions_{$groupName}";
                $this->formData[$key] = [];
            }
        }

        $this->form->fill($this->formData);
    }

    protected function getPermissionGroups(): array
    {
        $permissions = Permission::all();
        $groups = [];

        $groups['Müşteri Yönetimi']['Müşteriler'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'customers.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();
            
        $groups['Müşteri Yönetimi']['Müşteri Grupları'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'customer_groups.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Müşteri Yönetimi']['Potansiyel Müşteriler'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'leads.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Hesap Yönetimi']['Banka Hesapları'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'bank_accounts.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Hesap Yönetimi']['Kredi Kartları'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'credit_cards.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Hesap Yönetimi']['Kripto Cüzdanlar'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'crypto_wallets.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Hesap Yönetimi']['Sanal POS'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'virtual_pos.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Finansal İşlemler']['İşlem ve Transfer'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'transactions.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Finansal İşlemler']['Devamlı İşlemler'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'recurring_transactions.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Finansal İşlemler']['Kredi İşlemleri'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'loans.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Finansal İşlemler']['Borç/Alacak İşlemleri'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'debts.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Yatırım ve Tasarruf']['Yatırım Planları'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'investments.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Yatırım ve Tasarruf']['Tasarruf Planları'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'savings.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Analiz ve Raporlama']['Nakit Akışı Raporu'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'reports.cash_flow'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();   

        $groups['Analiz ve Raporlama']['Kategori Analizi'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'reports.category_analysis'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Sistem Yönetimi']['Ayarlar'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'settings.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Sistem Yönetimi']['Roller'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'roles.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Sistem Yönetimi']['Kullanıcılar'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'users.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();
        
        $groups['Diğer']['Tedarikçiler'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'suppliers.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Diğer']['Proje Yönetimi'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'projects.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Diğer']['Kategori Yönetimi'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'categories.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();
        

        // Boş grupları temizle
        foreach ($groups as $groupName => $groupData) {
            if (is_array($groupData)) {
                foreach ($groupData as $subGroupName => $permissions) {
                    if (empty($permissions)) {
                        unset($groups[$groupName][$subGroupName]);
                    }
                }
                if (empty($groups[$groupName])) {
                    unset($groups[$groupName]);
                }
            } elseif (empty($groupData)) {
                unset($groups[$groupName]);
            }
        }

        return $groups;
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Rol Adı')
                    ->required()
                    ->maxLength(255),
                ...$this->buildPermissionGroups()
            ])
            ->statePath('formData');
    }

    protected function buildPermissionGroups(): array
    {
        $groups = $this->getPermissionGroups();
        $components = [];
        $firstGroup = true; // İlk grubu takip etmek için flag

        foreach ($groups as $groupName => $groupData) {
            if (is_array($groupData) && !isset($groupData['name'])) {
                $subComponents = [];
                foreach ($groupData as $subGroupName => $permissions) {
                    if (!empty($permissions) && is_array($permissions)) {
                        $subComponents[] = Section::make()
                            ->heading($subGroupName)
                            ->schema([
                                CheckboxList::make("permissions_{$groupName}_{$subGroupName}")
                                    ->label('')
                                    ->options($permissions)
                                    ->bulkToggleable()
                                    ->columns(2)
                            ]);
                    }
                }
                if (!empty($subComponents)) {
                    $components[] = Section::make($groupName)
                        ->schema($subComponents)
                        ->collapsible()
                        ->collapsed(!$firstGroup); // İlk grup değilse kapalı
                    $firstGroup = false; // İlk grup işlendi
                }
            } else {
                $permissionsInGroup = is_array($groupData) ? $groupData : [];
                $optionsKey = "permissions_{$groupName}";

                if (!empty($permissionsInGroup)) {
                    $components[] = Section::make()
                        ->heading($groupName)
                        ->schema([
                            CheckboxList::make($optionsKey)
                                ->label('')
                                ->options($permissionsInGroup)
                                ->bulkToggleable()
                                ->columns(2)
                        ])
                        ->collapsible()
                        ->collapsed(!$firstGroup); // İlk grup değilse kapalı
                    $firstGroup = false; // İlk grup işlendi
                }
            }
        }

        return $components;
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $selectedPermissions = [];

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'permissions_') && is_array($value)) {
                $selectedPermissions = array_merge($selectedPermissions, $value);
            }
        }

        $roleData = RoleData::fromArray([
            'name' => $data['name'],
            'permissions' => array_unique($selectedPermissions)
        ]);

        try {
            if ($this->isEdit) {
                $this->roleService->update($this->role, $roleData);
                Notification::make()
                    ->title('Rol güncellendi')
                    ->success()
                    ->send();
            } else {
                $this->roleService->create($roleData);
                Notification::make()
                    ->title('Rol oluşturuldu')
                    ->success()
                    ->send();
            }

            $this->redirectRoute('admin.roles.index', navigate: true);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata')
                ->body('İşlem sırasında bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancel(): void
    {
        $this->redirectRoute('admin.roles.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.role.role-form');
    }
}