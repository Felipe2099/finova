<?php

namespace App\Livewire\Role;

use Livewire\Component;
use Filament\Forms;
use Filament\Tables;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Notifications\Notification;
use App\Services\Role\Contracts\RoleServiceInterface;
use App\DTOs\Role\RoleData;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Forms\Components\Actions\Action;

/**
 * Rol Yönetimi Bileşeni
 * 
 * Bu bileşen, kullanıcı rollerinin yönetimini sağlar.
 * Özellikler:
 * - Rol listesi görüntüleme
 * - Yeni rol oluşturma
 * - Rol düzenleme
 * - Rol silme
 * - İzin yönetimi
 * - Toplu işlem desteği
 * 
 * @package App\Livewire\Role
 */
class RoleManager extends Component implements Tables\Contracts\HasTable, Forms\Contracts\HasForms
{
    use Tables\Concerns\InteractsWithTable;
    use Forms\Concerns\InteractsWithForms;

    /** @var RoleServiceInterface Rol servisi */
    private RoleServiceInterface $roleService;

    /**
     * Bileşen başlatılırken rol servisini enjekte eder
     * 
     * @param RoleServiceInterface $roleService Rol servisi
     * @return void
     */
    public function boot(RoleServiceInterface $roleService): void
    {
        $this->roleService = $roleService;
    }

    /**
     * İzin gruplarının yapılandırmasını tanımlar
     * 
     * @return array
     */
    protected function getPermissionGroupsConfig(): array
    {
        return [
            'Müşteri Yönetimi' => [
                'Müşteriler' => 'customers',
                'Müşteri Grupları' => 'customer_groups',
                'Potansiyel Müşteriler' => 'leads',
            ],
            'Proje Yönetimi' => 'projects',
            'Hesap Yönetimi' => [
                'Banka Hesapları' => 'bank_accounts',
                'Kredi Kartları' => 'credit_cards',
                'Kripto Cüzdanlar' => 'crypto_wallets',
                'Sanal POS' => 'virtual_pos',
            ],
            'Finansal İşlemler' => [
                'Kredi İşlemleri' => 'loans',
                'Borç/Alacak İşlemleri' => 'debts',
                'İşlem ve Transfer' => 'transactions',
            ],
            'Analiz ve Raporlama' => 'reports',
            'Kategori Yönetimi' => 'categories',
            'Sistem Yönetimi' => [
                'Ayarlar' => 'settings',
                'Roller' => 'roles',
                'Kullanıcılar' => 'users',
            ],
        ];
    }

    /**
     * İzin gruplarını oluşturur
     * 
     * @return array
     */
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

    /**
     * İzin form yapılandırmasını oluşturur
     */
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

    /**
     * Tablo yapılandırmasını oluşturur
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(Role::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Rol Adı')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Toplam İzin')
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Toplam Kullanıcı')
                    ->sortable(),
            ])
            ->emptyStateHeading('Rol Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir rol ekleyin.')
            ->defaultSort('name')
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->modalHeading('Rol Düzenle')
                        ->modalWidth('4xl')
                        ->modalSubmitActionLabel('Güncelle')
                        ->modalCancelActionLabel('İptal')
                        ->successNotificationTitle('Rol güncellendi')
                        ->label('Düzenle')
                        ->form($this->getFormSchema())
                        ->fillForm(function (Role $record): array {
                            $rolePermissions = $record->permissions->pluck('name')->toArray();
                            $formData = [
                                'name' => $record->name,
                            ];

                            $groups = $this->getPermissionGroups();

                            foreach ($groups as $groupName => $groupData) {
                                if (is_array($groupData) && !isset($groupData['name'])) {
                                    // Alt grupları olan kategori
                                    foreach ($groupData as $subGroupName => $permissions) {
                                        if (!empty($permissions) && is_array($permissions)) {
                                            $key = "permissions_{$groupName}_{$subGroupName}";
                                            $groupPermissions = array_keys($permissions);
                                            $selectedInGroup = array_intersect($rolePermissions, $groupPermissions);
                                            // Ensure the value is boolean true for selected items
                                            $formData[$key] = array_fill_keys($selectedInGroup, true);
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
                                        // Need to import or use the full namespace for Permission model here
                                        $permissionsInGroup = \Spatie\Permission\Models\Permission::where('name', 'like', $prefix . '%')
                                            ->pluck('display_name', 'name')
                                            ->toArray();
                                    }

                                    if(!empty($permissionsInGroup)) {
                                        $groupPermissionNames = array_keys($permissionsInGroup);
                                        $selectedInGroup = array_intersect($rolePermissions, $groupPermissionNames);
                                        // Ensure the value is boolean true for selected items
                                        $formData[$key] = array_fill_keys($selectedInGroup, true);
                                    }
                                }
                            }

                            return $formData;
                        })
                        ->using(function (Role $record, array $data): Role {
                            $selectedPermissions = [];
                            foreach ($data as $key => $value) {
                                if (str_starts_with($key, 'permissions_') && is_array($value)) {
                                    $selectedPermissions = array_merge($selectedPermissions, array_keys(array_filter($value)));
                                }
                            }
                            $roleData = RoleData::fromArray(['name' => $data['name'], 'permissions' => array_unique($selectedPermissions)]);
                            return $this->roleService->update($record, $roleData);
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->modalHeading('Rol Sil')
                        ->modalDescription('Bu rolü silmek istediğinize emin misiniz?')
                        ->modalSubmitActionLabel('Sil')
                        ->modalCancelActionLabel('İptal')
                        ->successNotificationTitle('Rol silindi')
                        ->label('Sil')
                        ->using(function (Role $record): void {
                            $this->roleService->delete($record);
                        }),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Rol Oluştur')
                    ->modalHeading('Yeni Rol')
                    ->modalWidth('4xl')
                    ->modalSubmitActionLabel('Oluştur')
                    ->modalCancelActionLabel('İptal')
                    ->createAnother(false)
                    ->successNotificationTitle('Rol oluşturuldu')
                    ->form($this->getFormSchema())
                    ->using(function (array $data): Role {
                        $selectedPermissions = [];
                        foreach ($data as $key => $value) {
                            if (str_starts_with($key, 'permissions_') && is_array($value)) {
                                $selectedPermissions = array_merge($selectedPermissions, array_keys(array_filter($value)));
                            }
                        }
                        $roleData = RoleData::fromArray(['name' => $data['name'], 'permissions' => array_unique($selectedPermissions)]);
                        return $this->roleService->create($roleData);
                    }),
            ])
            ->actionsColumnLabel('İşlemler');
    }

    /**
     * İzin gruplarını oluşturan metot
     * 
     * @return array
     */
    protected function buildPermissionGroups(): array
    {
        $groups = $this->getPermissionGroups();
        $components = [];
        
        // Ana kategorileri oluştur
        foreach ($groups as $groupName => $groupData) {
            if (is_array($groupData) && !isset($groupData['name'])) {
                // Alt grupları olan bir kategori
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
                        ->collapsible();
                }
            } else {
                // Tek seviyeli bir kategori
                if (!empty($groupData) && is_array($groupData)) {
                    $components[] = Section::make()
                        ->heading($groupName)
                        ->schema([
                            CheckboxList::make("permissions_{$groupName}")
                                ->label('')
                                ->options($groupData)
                                ->bulkToggleable()
                                ->columns(2)
                        ])
                        ->collapsible();
                } elseif (!empty($groupData)) {
                    // String durumunu ele al
                    $prefix = $groupData . '.';
                    $filteredPermissions = Permission::where('name', 'like', $prefix . '%')
                        ->get()
                        ->mapWithKeys(fn ($permission) => [$permission->name => $permission->display_name])
                        ->toArray();
                    
                    if (!empty($filteredPermissions)) {
                        $components[] = Section::make()
                            ->heading($groupName)
                            ->schema([
                                CheckboxList::make("permissions_{$groupName}")
                                    ->label('')
                                    ->options($filteredPermissions)
                                    ->bulkToggleable()
                                    ->columns(2)
                            ])
                            ->collapsible();
                    }
                }
            }
        }
            
        return $components;
    }
    
    /**
     * Bileşenin görünümünü render eder
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.role.manager');
    }
}