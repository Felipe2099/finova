<?php

declare(strict_types=1);

namespace App\Services\User\Contracts;

use App\Models\User;
use App\DTOs\User\UserData;
use App\DTOs\User\UserUpdateData;
use App\DTOs\User\UserLoginData;
use App\DTOs\User\UserPasswordResetData;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Kullanıcı servisi arayüzü
 * 
 * Kullanıcı işlemlerinin yönetimi için gerekli metodları tanımlar.
 * Kullanıcıların oluşturulması, güncellenmesi, silinmesi, yetkilendirilmesi ve
 * komisyon yönetimi işlemlerini yapar.
 */
interface UserServiceInterface
{
    /**
     * Yeni bir kullanıcı oluşturur
     * 
     * @param UserData $data Kullanıcı verileri
     * @return User Oluşturulan kullanıcı
     */
    public function create(UserData $data): User;

    /**
     * Mevcut bir kullanıcıyı günceller
     * 
     * @param User $user Güncellenecek kullanıcı
     * @param UserData $data Yeni kullanıcı verileri
     * @return User Güncellenmiş kullanıcı
     */
    public function update(User $user, UserData $data): User;

    /**
     * Kullanıcıyı siler (soft delete)
     * 
     * @param User $user Silinecek kullanıcı
     * @param bool $shouldNotify Bildirim gösterilip gösterilmeyeceği
     */
    public function delete(User $user, bool $shouldNotify = true): void;

    /**
     * Silinmiş kullanıcıyı geri yükler
     * 
     * @param User $user Geri yüklenecek kullanıcı
     * @param bool $shouldNotify Bildirim gösterilip gösterilmeyeceği
     * @return User Geri yüklenen kullanıcı
     */
    public function restore(User $user, bool $shouldNotify = true): User;

    /**
     * Kullanıcının şifresini günceller
     * 
     * @param User $user Şifresi güncellenecek kullanıcı
     * @param string $password Yeni şifre
     * @return User Şifresi güncellenmiş kullanıcı
     */
    public function updatePassword(User $user, string $password): User;


    /**
     * Kullanıcı girişini yapar
     * 
     * @param UserLoginData $data Giriş verileri
     * @return Authenticatable|null Giriş yapan kullanıcı veya null
     */
    public function login(UserLoginData $data): ?Authenticatable;
} 