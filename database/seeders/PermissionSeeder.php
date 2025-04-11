<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Mevcut izinleri temizle
        Schema::disableForeignKeyConstraints();
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('permissions')->truncate();
        Schema::enableForeignKeyConstraints();

        // 1. Müşteri Yönetimi İzinleri
        // 1.1 Müşteri CRUD
        Permission::create(['name' => 'customers.view', 'guard_name' => 'web', 'display_name' => 'Müşterileri Görüntüle']);
        Permission::create(['name' => 'customers.create', 'guard_name' => 'web', 'display_name' => 'Müşteri Oluştur']);
        Permission::create(['name' => 'customers.edit', 'guard_name' => 'web', 'display_name' => 'Müşteri Düzenle']);
        Permission::create(['name' => 'customers.delete', 'guard_name' => 'web', 'display_name' => 'Müşteri Sil']);
        Permission::create(['name' => 'customers.detail', 'guard_name' => 'web', 'display_name' => 'Müşteri Detayını Görüntüle']);
        Permission::create(['name' => 'customers.credentials', 'guard_name' => 'web', 'display_name' => 'Müşteri Hassas Bilgileri']);
        Permission::create(['name' => 'customers.agreements', 'guard_name' => 'web', 'display_name' => 'Müşteri Anlaşmaları']);

        // 1.2 Müşteri Grubu CRUD
        Permission::create(['name' => 'customer_groups.view', 'guard_name' => 'web', 'display_name' => 'Müşteri Grubu Görüntüle']);
        Permission::create(['name' => 'customer_groups.create', 'guard_name' => 'web', 'display_name' => 'Müşteri Grubu Oluştur']);
        Permission::create(['name' => 'customer_groups.edit', 'guard_name' => 'web', 'display_name' => 'Müşteri Grubu Düzenle']);
        Permission::create(['name' => 'customer_groups.delete', 'guard_name' => 'web', 'display_name' => 'Müşteri Grubu Sil']);

        // 1.3 Lead CRUD
        Permission::create(['name' => 'leads.view', 'guard_name' => 'web', 'display_name' => 'Potansiyel Müşteri Görüntüle']);
        Permission::create(['name' => 'leads.create', 'guard_name' => 'web', 'display_name' => 'Potansiyel Müşteri Oluştur']);
        Permission::create(['name' => 'leads.edit', 'guard_name' => 'web', 'display_name' => 'Potansiyel Müşteri Düzenle']);
        Permission::create(['name' => 'leads.delete', 'guard_name' => 'web', 'display_name' => 'Potansiyel Müşteri Sil']);
        Permission::create(['name' => 'leads.convert_customer', 'guard_name' => 'web', 'display_name' => 'Müşteriye Çevir']);

        // 2. Proje CRUD
        Permission::create(['name' => 'projects.view', 'guard_name' => 'web', 'display_name' => 'Projeleri Görüntüle']);
        Permission::create(['name' => 'projects.details', 'guard_name' => 'web', 'display_name' => 'Proje Detayları']);
        Permission::create(['name' => 'projects.create', 'guard_name' => 'web', 'display_name' => 'Proje Oluştur']);
        Permission::create(['name' => 'projects.edit', 'guard_name' => 'web', 'display_name' => 'Proje Düzenle']);
        Permission::create(['name' => 'projects.delete', 'guard_name' => 'web', 'display_name' => 'Proje Sil']);

        // 3. Hesap Yönetimi İzinleri
        // 3.1 Banka Hesapları CRUD
        Permission::create(['name' => 'bank_accounts.view', 'guard_name' => 'web', 'display_name' => 'Banka Hesaplarını Görüntüle']);
        Permission::create(['name' => 'bank_accounts.create', 'guard_name' => 'web', 'display_name' => 'Banka Hesabı Oluştur']);
        Permission::create(['name' => 'bank_accounts.edit', 'guard_name' => 'web', 'display_name' => 'Banka Hesabı Düzenle']);
        Permission::create(['name' => 'bank_accounts.delete', 'guard_name' => 'web', 'display_name' => 'Banka Hesabı Sil']);
        Permission::create(['name' => 'bank_accounts.history', 'guard_name' => 'web', 'display_name' => 'Banka Hesap Geçmişi']);
        Permission::create(['name' => 'bank_accounts.transactions', 'guard_name' => 'web', 'display_name' => 'Banka Hesap İşlemleri']);
        Permission::create(['name' => 'bank_accounts.transfers', 'guard_name' => 'web', 'display_name' => 'Banka Hesap Transferleri']);

        // 3.2 Kredi Kartları CRUD
        Permission::create(['name' => 'credit_cards.view', 'guard_name' => 'web', 'display_name' => 'Kredi Kartlarını Görüntüle']);
        Permission::create(['name' => 'credit_cards.create', 'guard_name' => 'web', 'display_name' => 'Kredi Kartı Oluştur']);
        Permission::create(['name' => 'credit_cards.edit', 'guard_name' => 'web', 'display_name' => 'Kredi Kartı Düzenle']);
        Permission::create(['name' => 'credit_cards.delete', 'guard_name' => 'web', 'display_name' => 'Kredi Kartı Sil']);
        Permission::create(['name' => 'credit_cards.history', 'guard_name' => 'web', 'display_name' => 'Kredi Kartı Geçmişi']);
        Permission::create(['name' => 'credit_cards.payments', 'guard_name' => 'web', 'display_name' => 'Kredi Kartı Ödemeleri']);

        // 3.3 Kripto Cüzdanlar CRUD
        Permission::create(['name' => 'crypto_wallets.view', 'guard_name' => 'web', 'display_name' => 'Kripto Cüzdanları Görüntüle']);
        Permission::create(['name' => 'crypto_wallets.create', 'guard_name' => 'web', 'display_name' => 'Kripto Cüzdan Oluştur']);
        Permission::create(['name' => 'crypto_wallets.edit', 'guard_name' => 'web', 'display_name' => 'Kripto Cüzdan Düzenle']);
        Permission::create(['name' => 'crypto_wallets.delete', 'guard_name' => 'web', 'display_name' => 'Kripto Cüzdan Sil']);
        Permission::create(['name' => 'crypto_wallets.transfer', 'guard_name' => 'web', 'display_name' => 'Kripto Cüzdan Transferi']);

        // 3.4 Sanal POS CRUD
        Permission::create(['name' => 'virtual_pos.view', 'guard_name' => 'web', 'display_name' => 'Sanal POS Görüntüle']);
        Permission::create(['name' => 'virtual_pos.create', 'guard_name' => 'web', 'display_name' => 'Sanal POS Oluştur']);
        Permission::create(['name' => 'virtual_pos.edit', 'guard_name' => 'web', 'display_name' => 'Sanal POS Düzenle']);
        Permission::create(['name' => 'virtual_pos.delete', 'guard_name' => 'web', 'display_name' => 'Sanal POS Sil']);
        Permission::create(['name' => 'virtual_pos.transfer', 'guard_name' => 'web', 'display_name' => 'Sanal POS Transferleri']);

        // 4. Finansal İşlem İzinleri
        // 4.1 Kredi CRUD
        Permission::create(['name' => 'loans.view', 'guard_name' => 'web', 'display_name' => 'Kredileri Görüntüle']);
        Permission::create(['name' => 'loans.create', 'guard_name' => 'web', 'display_name' => 'Kredi Oluştur']);
        Permission::create(['name' => 'loans.edit', 'guard_name' => 'web', 'display_name' => 'Kredi Düzenle']);
        Permission::create(['name' => 'loans.delete', 'guard_name' => 'web', 'display_name' => 'Kredi Sil']);
        Permission::create(['name' => 'loans.payments', 'guard_name' => 'web', 'display_name' => 'Kredi Ödemeleri']);

        // 4.2 Borç/Alacak CRUD
        Permission::create(['name' => 'debts.view', 'guard_name' => 'web', 'display_name' => 'Borç/Alacakları Görüntüle']);
        Permission::create(['name' => 'debts.create', 'guard_name' => 'web', 'display_name' => 'Borç/Alacak Oluştur']);
        Permission::create(['name' => 'debts.edit', 'guard_name' => 'web', 'display_name' => 'Borç/Alacak Düzenle']);
        Permission::create(['name' => 'debts.delete', 'guard_name' => 'web', 'display_name' => 'Borç/Alacak Sil']);

        // 4.3 İşlem CRUD
        Permission::create(['name' => 'transactions.view', 'guard_name' => 'web', 'display_name' => 'İşlemleri Görüntüle']);
        Permission::create(['name' => 'transactions.create', 'guard_name' => 'web', 'display_name' => 'İşlem Oluştur']);
        Permission::create(['name' => 'transactions.edit', 'guard_name' => 'web', 'display_name' => 'İşlem Düzenle']);
        Permission::create(['name' => 'transactions.delete', 'guard_name' => 'web', 'display_name' => 'İşlem Sil']);

        // 4.3.2 Devamlı işlemler
        Permission::create(['name' => 'recurring_transactions.view', 'guard_name' => 'web', 'display_name' => 'Devamlı İşlemleri Görüntüle']);
        Permission::create(['name' => 'recurring_transactions.copy', 'guard_name' => 'web', 'display_name' => 'Devamlı İşlemi Kopyala']);
        Permission::create(['name' => 'recurring_transactions.complete', 'guard_name' => 'web', 'display_name' => 'Devamlı İşlem Bitir']);

        // 5. Analiz ve Raporlama İzinleri
        Permission::create(['name' => 'reports.cash_flow', 'guard_name' => 'web', 'display_name' => 'Nakit Akışı Raporu']);
        Permission::create(['name' => 'reports.category_analysis', 'guard_name' => 'web', 'display_name' => 'Kategori Analizi']);
        
        // 5.1.1 Tasarruf Planı Görüntüle
        Permission::create(['name' => 'savings.view', 'guard_name' => 'web', 'display_name' => 'Tasarruf Planı Görüntüle']);
        Permission::create(['name' => 'savings.create', 'guard_name' => 'web', 'display_name' => 'Tasarruf Planı Oluştur']);
        Permission::create(['name' => 'savings.edit', 'guard_name' => 'web', 'display_name' => 'Tasarruf Planı Düzenle']);
        Permission::create(['name' => 'savings.delete', 'guard_name' => 'web', 'display_name' => 'Tasarruf Planı Sil']);

        // 5.1.2 Yatırım Planı Görüntüle
        Permission::create(['name' => 'investments.view', 'guard_name' => 'web', 'display_name' => 'Yatırım Planı Görüntüle']);
        Permission::create(['name' => 'investments.create', 'guard_name' => 'web', 'display_name' => 'Yatırım Planı Oluştur']);
        Permission::create(['name' => 'investments.edit', 'guard_name' => 'web', 'display_name' => 'Yatırım Planı Düzenle']);
        Permission::create(['name' => 'investments.delete', 'guard_name' => 'web', 'display_name' => 'Yatırım Planı Sil']);

        // 6. Kategori CRUD
        Permission::create(['name' => 'categories.view', 'guard_name' => 'web', 'display_name' => 'Kategorileri Görüntüle']);
        Permission::create(['name' => 'categories.create', 'guard_name' => 'web', 'display_name' => 'Kategori Oluştur']);
        Permission::create(['name' => 'categories.edit', 'guard_name' => 'web', 'display_name' => 'Kategori Düzenle']);
        Permission::create(['name' => 'categories.delete', 'guard_name' => 'web', 'display_name' => 'Kategori Sil']);

        // 6.1 Tedarikçiler CRUD
        Permission::create(['name' => 'suppliers.view', 'guard_name' => 'web', 'display_name' => 'Tedarikçileri Görüntüle']);
        Permission::create(['name' => 'suppliers.create', 'guard_name' => 'web', 'display_name' => 'Tedarikçi Oluştur']);
        Permission::create(['name' => 'suppliers.edit', 'guard_name' => 'web', 'display_name' => 'Tedarikçi Düzenle']);
        Permission::create(['name' => 'suppliers.delete', 'guard_name' => 'web', 'display_name' => 'Tedarikçi Sil']);

        // 7. Sistem Yönetimi CRUD
        Permission::create(['name' => 'settings.view', 'guard_name' => 'web', 'display_name' => 'Ayarları Görüntüle']);
        Permission::create(['name' => 'settings.site', 'guard_name' => 'web', 'display_name' => 'Site Ayarları']);
        Permission::create(['name' => 'settings.notification', 'guard_name' => 'web', 'display_name' => 'Notification Ayarları']);
        Permission::create(['name' => 'settings.telegram', 'guard_name' => 'web', 'display_name' => 'Telegram Ayarları']);
        
        Permission::create(['name' => 'roles.view', 'guard_name' => 'web', 'display_name' => 'Rolleri Görüntüle']);
        Permission::create(['name' => 'roles.create', 'guard_name' => 'web', 'display_name' => 'Rol Oluştur']);
        Permission::create(['name' => 'roles.edit', 'guard_name' => 'web', 'display_name' => 'Rol Düzenle']);
        Permission::create(['name' => 'roles.delete', 'guard_name' => 'web', 'display_name' => 'Rol Sil']);

        Permission::create(['name' => 'users.view', 'guard_name' => 'web', 'display_name' => 'Kullanıcıları Görüntüle']);
        Permission::create(['name' => 'users.create', 'guard_name' => 'web', 'display_name' => 'Kullanıcı Oluştur']);
        Permission::create(['name' => 'users.edit', 'guard_name' => 'web', 'display_name' => 'Kullanıcı Düzenle']);
        Permission::create(['name' => 'users.delete', 'guard_name' => 'web', 'display_name' => 'Kullanıcı Sil']);

        Permission::create(['name' => 'users.commissions', 'guard_name' => 'web', 'display_name' => 'Komisyonları Görüntüle']);
        Permission::create(['name' => 'users.commission.payment', 'guard_name' => 'web', 'display_name' => 'Komisyon Ödemesi']);
        Permission::create(['name' => 'users.change_password', 'guard_name' => 'web', 'display_name' => 'Şifre Değiştir']);
    }
} 