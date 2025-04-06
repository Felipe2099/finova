<?php

declare(strict_types=1);

namespace App\Services\Role\Implementations;

use Spatie\Permission\Models\Role;
use App\Services\Role\Contracts\RoleServiceInterface;
use App\DTOs\Role\RoleData;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

/**
 * Rol servisi implementasyonu
 * 
 * Rol yönetimi için gerekli metodları içerir.
 * Rollerin oluşturulması, güncellenmesi ve silinmesi işlemlerini gerçekleştirir.
 */
final class RoleService implements RoleServiceInterface
{
    /**
     * Yeni bir rol oluşturur
     * 
     * @param RoleData $data Rol verileri
     * @return Role Oluşturulan rol
     */
    public function create(RoleData $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create(['name' => $data->name]);
            $role->syncPermissions($data->permissions ?? []);
            return $role;
        });
    }

    /**
     * Mevcut bir rolü günceller
     * 
     * @param Role $role Güncellenecek rol
     * @param RoleData $data Güncellenecek veriler
     * @return Role Güncellenmiş rol
     */
    public function update(Role $role, RoleData $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $role->update(['name' => $data->name]);
            $role->syncPermissions($data->permissions ?? []);
            return $role->fresh();
        });
    }

    /**
     * Bir rolü siler
     * 
     * @param Role $role Silinecek rol
     */
    public function delete(Role $role): void
    {
        DB::transaction(function () use ($role) {
            $role->delete();
        });
        Notification::make()
            ->title('Rol silindi')
            ->success()
            ->send();
    }
}