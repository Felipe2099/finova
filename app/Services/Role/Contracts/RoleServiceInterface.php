<?php

declare(strict_types=1);

namespace App\Services\Role\Contracts;

use Spatie\Permission\Models\Role;
use App\DTOs\Role\RoleData;

/**
 * Rol servisi arayüzü
 * 
 * Rol yönetimi için gerekli metodları tanımlar.
 * Rollerin oluşturulması, güncellenmesi ve silinmesi işlemlerini içerir.
 */
interface RoleServiceInterface
{
    /**
     * Yeni bir rol oluşturur
     * 
     * @param RoleData $data Rol verileri
     * @return Role Oluşturulan rol
     */
    public function create(RoleData $data): Role;

    /**
     * Mevcut bir rolü günceller
     * 
     * @param Role $role Güncellenecek rol
     * @param RoleData $data Güncellenecek veriler
     * @return Role Güncellenmiş rol
     */
    public function update(Role $role, RoleData $data): Role;

    /**
     * Bir rolü siler
     * 
     * @param Role $role Silinecek rol
     */
    public function delete(Role $role): void;
}