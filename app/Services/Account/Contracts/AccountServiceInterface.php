<?php

namespace App\Services\Account\Contracts;

use App\Models\Account;
use App\DTOs\Account\AccountData;

/**
 * Hesap servisi arayüzü
 * 
 * Hesap işlemlerinin yönetimi için gerekli metodları tanımlar.
 * Hesapların oluşturulması, güncellenmesi, silinmesi ve diğer hesap işlemlerini yapar.
 */
interface AccountServiceInterface
{
    /**
     * Yeni bir hesap oluşturur
     * 
     * @param AccountData $data Hesap verileri
     * @return Account Oluşturulan hesap
     */
    public function createAccount(AccountData $data): Account;

    /**
     * Mevcut bir hesabı günceller
     * 
     * @param Account $account Güncellenecek hesap
     * @param AccountData $data Yeni hesap verileri
     * @return Account Güncellenmiş hesap
     */
    public function updateAccount(Account $account, AccountData $data): Account;

    /**
     * Hesabı siler
     * 
     * @param Account $account Silinecek hesap
     * @return bool İşlem başarılı ise true, değilse false
     */
    public function delete(Account $account): bool;

    /**
     * Taksitli alışveriş işlemi oluşturur
     * 
     * @param AccountData $data Taksitli alışveriş verileri
     * @return Transaction Oluşturulan taksitli alışveriş işlemi
     */
    public function createInstallmentPurchase(AccountData $data): \App\Models\Transaction;

    /**
     * Kredi kartı için taksitli ödemeleri getirir
     * 
     * @param int $accountId Kredi kartı hesap ID'si
     * @return \Illuminate\Database\Eloquent\Collection Taksitli ödemeler
     */
    public function getInstallmentsForCard(int $accountId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Kripto cüzdan hesabı oluşturur
     * 
     * @param AccountData $data Kripto cüzdan verileri
     * @return Account Oluşturulan kripto cüzdan hesabı
     */
    public function createCryptoWallet(AccountData $data): Account;

    /**
     * Sanal POS hesabı oluşturur
     * 
     * @param AccountData $data Sanal POS verileri
     * @return Account Oluşturulan sanal POS hesabı
     */
    public function createVirtualPos(AccountData $data): Account;
}