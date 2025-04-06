<?php

declare(strict_types=1);

namespace App\Services\User\Implementations;

use App\Models\User;
use App\Services\User\Contracts\UserServiceInterface;
use App\DTOs\User\UserData;
use App\DTOs\User\UserUpdateData;
use App\DTOs\User\UserLoginData;
use App\DTOs\User\UserPasswordResetData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Contracts\Auth\Authenticatable;
use Filament\Notifications\Notification;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Support\Str;

/**
 * Kullanıcı servisi implementasyonu
 * 
 * Kullanıcı işlemlerinin yönetimi için gerekli metodları içerir.
 * Kullanıcıların oluşturulması, güncellenmesi, silinmesi, yetkilendirilmesi ve
 * komisyon yönetimi işlemlerini gerçekleştirir.
 */
class UserService implements UserServiceInterface
{
    /**
     * Yeni bir kullanıcı oluşturur
     * 
     * @param UserData $data Kullanıcı verileri
     * @return User Oluşturulan kullanıcı
     */
    public function create(UserData $data): User
    {
        return DB::transaction(function () use ($data) {
            // Kullanıcıyı oluştur
            $user = User::create($data->toModelData());
            
            // Eğer rol bilgisi varsa rolleri ata
            if (!empty($data->roles)) {
                $user->assignRole($data->roles);
            }
            
            return $user;
        });
    }

    /**
     * Mevcut bir kullanıcıyı günceller
     * 
     * @param User $user Güncellenecek kullanıcı
     * @param UserData $data Yeni kullanıcı verileri
     * @return User Güncellenmiş kullanıcı
     */
    public function update(User $user, UserData $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // Kullanıcıyı güncelle
            $user->update($data->toModelData());
            
            // Eğer rol bilgisi varsa rolleri güncelle
            if ($data->roles !== null) {
                $user->syncRoles($data->roles);
            }
            
            return $user->fresh();
        });
    }

    /**
     * Kullanıcıyı siler (soft delete)
     * 
     * @param User $user Silinecek kullanıcı
     * @param bool $shouldNotify Bildirim gösterilip gösterilmeyeceği
     */
    public function delete(User $user, bool $shouldNotify = true): void
    {
        DB::transaction(function () use ($user, $shouldNotify) {
            // Kullanıcıyı sil (soft delete)
            $user->delete();

            // Eğer bildirim isteniyorsa
            if ($shouldNotify) {
                Notification::make('user-deleted')
                    ->title('Kullanıcı silindi')
                    ->success()
                    ->send();
            }
        });
    }

    /**
     * Silinmiş kullanıcıyı geri yükler
     * 
     * @param User $user Geri yüklenecek kullanıcı
     * @param bool $shouldNotify Bildirim gösterilip gösterilmeyeceği
     * @return User Geri yüklenen kullanıcı
     */
    public function restore(User $user, bool $shouldNotify = true): User
    {
        return DB::transaction(function () use ($user, $shouldNotify) {
            // Kullanıcıyı geri yükle
            $user->restore();
            
            // Eğer bildirim isteniyorsa
            if ($shouldNotify) {
                Notification::make()
                    ->title('Kullanıcı geri yüklendi')
                    ->success()
                    ->send();
            }
            
            return $user->fresh();
        });
    }

    /**
     * Kullanıcının şifresini günceller
     * 
     * @param User $user Şifresi güncellenecek kullanıcı
     * @param string $password Yeni şifre
     * @return User Şifresi güncellenmiş kullanıcı
     */
    public function updatePassword(User $user, string $password): User
    {
        return DB::transaction(function () use ($user, $password) {
            $user->update([
                'password' => Hash::make($password),
            ]);
            
            return $user->fresh();
        });
    }

    /**
     * Kullanıcı girişini yapar
     * 
     * @param UserLoginData $data Giriş verileri
     * @return Authenticatable|null Giriş yapan kullanıcı veya null
     */
    public function login(UserLoginData $data): ?Authenticatable
    {
        $credentials = $data->credentials();
        $remember = $data->remember_me;
        
        if (Auth::attempt($credentials, $remember)) {
            return Auth::user();
        }
        
        return null;
    }

    /**
     * Kullanıcı şifre sıfırlamayı başlatır
     * 
     * @param string $email Şifresi sıfırlanacak kullanıcı e-postası
     * @return bool İşlem başarılı mı
     */
    public function initiatePasswordReset(string $email): bool
    {
        $status = Password::sendResetLink(['email' => $email]);
        
        return $status === Password::RESET_LINK_SENT;
    }

    /**
     * Kullanıcı şifresini sıfırlar
     * 
     * @param UserPasswordResetData $data Şifre sıfırlama verileri
     * @return bool İşlem başarılı mı
     */
    public function resetPassword(UserPasswordResetData $data): bool
    {
        $status = Password::reset(
            $data->credentials(),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordResetEvent($user));
            }
        );
        
        return $status === Password::PASSWORD_RESET;
    }
} 