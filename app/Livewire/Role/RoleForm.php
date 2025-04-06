<?php

namespace App\Livewire\Role;

use Livewire\Component;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Services\Role\Contracts\RoleServiceInterface;
use App\DTOs\Role\RoleData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

/**
 * Rol Oluşturma/Düzenleme Formu Bileşeni
 */
class RoleForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?Role $role = null;
    public bool $isEdit = false;

    public string $name = '';
    public array $formData = []; // To hold all form data including permissions

    private RoleServiceInterface $roleService;

    public function boot(RoleServiceInterface $roleService): void
    {
        $this->roleService = $roleService;
    }

    public function mount(?Role $role = null): void
    {
        $this->role = $role;
        $this->isEdit = !is_null($role);

        $initialData = [
            'name' => $this->isEdit ? $this->role->name : '',
        ];

        if ($this->isEdit) {
            $rolePermissions = $this->role->permissions->pluck('name')->toArray();
            $groups = $this->getPermissionGroups();

            foreach ($groups as $groupName => $groupData) {
                if (is_array($groupData) && !isset($groupData['name'])) {
                    // Alt grupları olan kategori
                    foreach ($groupData as $subGroupName => $permissions) {
                        if (!empty($permissions) && is_array($permissions)) {
                            $key = "permissions_{$groupName}_{$subGroupName}";
                            $groupPermissions = array_keys($permissions);
                            $selectedInGroup = array_intersect($rolePermissions, $groupPermissions);
                            $initialData[$key] = array_fill_keys($selectedInGroup, true);
                        }
                    }
                } else {
                    // Tek seviyeli kategori veya string tanımı
                    $key = "permissions_{$groupName}";
                    $permissionsInGroup = [];
                    if (is_array($groupData)) {
                        $permissionsInGroup = $groupData;
                    } elseif (is_string($groupData)) {
                        $prefix = $groupData . '.';
                        $permissionsInGroup = Permission::where('name', 'like', $prefix . '%')
                            ->pluck('display_name', 'name')
                            ->toArray();
                    }

                    if(!empty($permissionsInGroup)) {
                        $groupPermissionNames = array_keys($permissionsInGroup);
                        $selectedInGroup = array_intersect($rolePermissions, $groupPermissionNames);
                        $initialData[$key] = array_fill_keys($selectedInGroup, true);
                    }
                }
            }
        }
        
        // Fill the form using the 'formData' property
        $this->formData = $initialData;
        $this->form->fill($this->formData); 
    }
    
    // We need to reference the form data property in the form definition
    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('formData'); // Bind form state to the component's formData property
    }

    protected function getFormSchema(): array
    {
        $permissionSections = $this->buildPermissionGroups();
        
        return [
            TextInput::make('name')
                ->label('Rol Adı')
                ->required()
                ->maxLength(255),

            // İzin gruplarını ekle
            ...$permissionSections
        ];
    }

    protected function buildPermissionGroups(): array
    {
        $groups = $this->getPermissionGroups();
        $components = [];
        
        foreach ($groups as $groupName => $groupData) {
            if (is_array($groupData) && !isset($groupData['name'])) {
                // Alt grupları olan kategori
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
                            ])->collapsible()->collapsed(false); // Start expanded
                    }
                }
                if (!empty($subComponents)) {
                    $components[] = Section::make($groupName)
                        ->schema($subComponents)
                        ->collapsible()->collapsed(false); // Start expanded
                }
            } else {
                // Tek seviyeli kategori veya string tanımı
                $permissionsInGroup = [];
                if (is_array($groupData)) {
                     $permissionsInGroup = $groupData;
                } elseif (is_string($groupData)) {
                     $prefix = $groupData . '.';
                     $permissionsInGroup = Permission::where('name', 'like', $prefix . '%')
                         ->pluck('display_name', 'name')
                         ->toArray();
                }

                if (!empty($permissionsInGroup)) {
                    $components[] = Section::make()
                        ->heading($groupName)
                        ->schema([
                            CheckboxList::make("permissions_{$groupName}")
                                ->label('')
                                ->options($permissionsInGroup)
                                ->bulkToggleable()
                                ->columns(2)
                        ])
                        ->collapsible()->collapsed(false); // Start expanded
                }
            }
        }
            
        return $components;
    }

    // Duplicate of the method in RoleManager - consider moving to a trait or helper if reused more
    protected function getPermissionGroups(): array
    {
        $permissions = Permission::all();
        $groups = [];

        // Müşteri Yönetimi
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

        // Proje Yönetimi
        $groups['Proje Yönetimi'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'projects.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        // Hesap Yönetimi
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

        // Finansal İşlemler
        $groups['Finansal İşlemler']['Kredi İşlemleri'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'loans.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Finansal İşlemler']['Borç/Alacak İşlemleri'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'debts.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        $groups['Finansal İşlemler']['İşlem ve Transfer'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'transactions.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        // Analiz ve Raporlama
        $groups['Analiz ve Raporlama'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'reports.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        // Kategori Yönetimi
        $groups['Kategori Yönetimi'] = $permissions
            ->filter(fn ($permission) => str_starts_with($permission->name, 'categories.'))
            ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
            ->toArray();

        // Sistem Yönetimi
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

        // Boş grupları temizle
        foreach ($groups as $groupName => $groupData) {
            if (is_array($groupData)) {
                foreach ($groupData as $subGroupName => $permissionsList) { // Renamed to avoid conflict
                    if (empty($permissionsList)) {
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

    public function save(): void
    {
        $data = $this->form->getState();

        $selectedPermissions = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'permissions_') && is_array($value)) {
                $selectedPermissions = array_merge($selectedPermissions, array_keys(array_filter($value)));
            }
        }
        
        $roleData = RoleData::fromArray(['name' => $data['name'], 'permissions' => array_unique($selectedPermissions)]);

        try {
            if ($this->isEdit) {
                $this->roleService->update($this->role, $roleData);
                Notification::make()
                    ->title('Rol başarıyla güncellendi')
                    ->success()
                    ->send();
            } else {
                $this->roleService->create($roleData);
                Notification::make()
                    ->title('Rol başarıyla oluşturuldu')
                    ->success()
                    ->send();
            }
            $this->redirectRoute('roles.index');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render(): View
    {
        return view('livewire.role.role-form');
    }
} 