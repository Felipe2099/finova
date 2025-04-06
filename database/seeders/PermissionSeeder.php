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

        // 1.2 Müşteri Grubu CRUD
        Permission::create(['name' => 'customer_groups.view', 'guard_name' => 'web', 'display_name' => 'Müşteri Gruplarını Görüntüle']);
        Permission::create(['name' => 'customer_groups.create', 'guard_name' => 'web', 'display_name' => 'Müşteri Grubu Oluştur']);
        Permission::create(['name' => 'customer_groups.edit', 'guard_name' => 'web', 'display_name' => 'Müşteri Grubu Düzenle']);
        Permission::create(['name' => 'customer_groups.delete', 'guard_name' => 'web', 'display_name' => 'Müşteri Grubu Sil']);

        // 1.3 Lead CRUD
        Permission::create(['name' => 'leads.view', 'guard_name' => 'web', 'display_name' => 'Potansiyel Müşterileri Görüntüle']);
        Permission::create(['name' => 'leads.create', 'guard_name' => 'web', 'display_name' => 'Potansiyel Müşteri Oluştur']);
        Permission::create(['name' => 'leads.edit', 'guard_name' => 'web', 'display_name' => 'Potansiyel Müşteri Düzenle']);
        Permission::create(['name' => 'leads.delete', 'guard_name' => 'web', 'display_name' => 'Potansiyel Müşteri Sil']);

        // 2. Proje CRUD
        Permission::create(['name' => 'projects.view', 'guard_name' => 'web', 'display_name' => 'Projeleri Görüntüle']);
        Permission::create(['name' => 'projects.create', 'guard_name' => 'web', 'display_name' => 'Proje Oluştur']);
        Permission::create(['name' => 'projects.edit', 'guard_name' => 'web', 'display_name' => 'Proje Düzenle']);
        Permission::create(['name' => 'projects.delete', 'guard_name' => 'web', 'display_name' => 'Proje Sil']);

        // 3. Hesap Yönetimi İzinleri
        // 3.1 Banka Hesapları CRUD
        Permission::create(['name' => 'bank_accounts.view', 'guard_name' => 'web', 'display_name' => 'Banka Hesaplarını Görüntüle']);
        Permission::create(['name' => 'bank_accounts.create', 'guard_name' => 'web', 'display_name' => 'Banka Hesabı Oluştur']);
        Permission::create(['name' => 'bank_accounts.edit', 'guard_name' => 'web', 'display_name' => 'Banka Hesabı Düzenle']);
        Permission::create(['name' => 'bank_accounts.delete', 'guard_name' => 'web', 'display_name' => 'Banka Hesabı Sil']);

        // 3.2 Kredi Kartları CRUD
        Permission::create(['name' => 'credit_cards.view', 'guard_name' => 'web', 'display_name' => 'Kredi Kartlarını Görüntüle']);
        Permission::create(['name' => 'credit_cards.create', 'guard_name' => 'web', 'display_name' => 'Kredi Kartı Oluştur']);
        Permission::create(['name' => 'credit_cards.edit', 'guard_name' => 'web', 'display_name' => 'Kredi Kartı Düzenle']);
        Permission::create(['name' => 'credit_cards.delete', 'guard_name' => 'web', 'display_name' => 'Kredi Kartı Sil']);

        // 3.3 Kripto Cüzdanlar CRUD
        Permission::create(['name' => 'crypto_wallets.view', 'guard_name' => 'web', 'display_name' => 'Kripto Cüzdanları Görüntüle']);
        Permission::create(['name' => 'crypto_wallets.create', 'guard_name' => 'web', 'display_name' => 'Kripto Cüzdan Oluştur']);
        Permission::create(['name' => 'crypto_wallets.edit', 'guard_name' => 'web', 'display_name' => 'Kripto Cüzdan Düzenle']);
        Permission::create(['name' => 'crypto_wallets.delete', 'guard_name' => 'web', 'display_name' => 'Kripto Cüzdan Sil']);

        // 3.4 Sanal POS CRUD
        Permission::create(['name' => 'virtual_pos.view', 'guard_name' => 'web', 'display_name' => 'Sanal POS Görüntüle']);
        Permission::create(['name' => 'virtual_pos.create', 'guard_name' => 'web', 'display_name' => 'Sanal POS Oluştur']);
        Permission::create(['name' => 'virtual_pos.edit', 'guard_name' => 'web', 'display_name' => 'Sanal POS Düzenle']);
        Permission::create(['name' => 'virtual_pos.delete', 'guard_name' => 'web', 'display_name' => 'Sanal POS Sil']);

        // 4. Finansal İşlem İzinleri
        // 4.1 Kredi CRUD
        Permission::create(['name' => 'loans.view', 'guard_name' => 'web', 'display_name' => 'Kredileri Görüntüle']);
        Permission::create(['name' => 'loans.create', 'guard_name' => 'web', 'display_name' => 'Kredi Oluştur']);
        Permission::create(['name' => 'loans.edit', 'guard_name' => 'web', 'display_name' => 'Kredi Düzenle']);
        Permission::create(['name' => 'loans.delete', 'guard_name' => 'web', 'display_name' => 'Kredi Sil']);

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

        // 5. Analiz ve Raporlama İzinleri
        Permission::create(['name' => 'reports.cash_flow', 'guard_name' => 'web', 'display_name' => 'Nakit Akışı Raporu']);
        Permission::create(['name' => 'reports.category_analysis', 'guard_name' => 'web', 'display_name' => 'Kategori Analizi']);
        Permission::create(['name' => 'reports.savings', 'guard_name' => 'web', 'display_name' => 'Tasarruf Raporu']);
        Permission::create(['name' => 'reports.investments', 'guard_name' => 'web', 'display_name' => 'Yatırım Raporu']);

        // 6. Kategori CRUD
        Permission::create(['name' => 'categories.view', 'guard_name' => 'web', 'display_name' => 'Kategorileri Görüntüle']);
        Permission::create(['name' => 'categories.create', 'guard_name' => 'web', 'display_name' => 'Kategori Oluştur']);
        Permission::create(['name' => 'categories.edit', 'guard_name' => 'web', 'display_name' => 'Kategori Düzenle']);
        Permission::create(['name' => 'categories.delete', 'guard_name' => 'web', 'display_name' => 'Kategori Sil']);

        // 7. Sistem Yönetimi CRUD
        Permission::create(['name' => 'settings.view', 'guard_name' => 'web', 'display_name' => 'Ayarları Görüntüle']);
        Permission::create(['name' => 'settings.edit', 'guard_name' => 'web', 'display_name' => 'Ayarları Düzenle']);
        Permission::create(['name' => 'roles.view', 'guard_name' => 'web', 'display_name' => 'Rolleri Görüntüle']);
        Permission::create(['name' => 'roles.create', 'guard_name' => 'web', 'display_name' => 'Rol Oluştur']);
        Permission::create(['name' => 'roles.edit', 'guard_name' => 'web', 'display_name' => 'Rol Düzenle']);
        Permission::create(['name' => 'roles.delete', 'guard_name' => 'web', 'display_name' => 'Rol Sil']);
        Permission::create(['name' => 'users.view', 'guard_name' => 'web', 'display_name' => 'Kullanıcıları Görüntüle']);
        Permission::create(['name' => 'users.create', 'guard_name' => 'web', 'display_name' => 'Kullanıcı Oluştur']);
        Permission::create(['name' => 'users.edit', 'guard_name' => 'web', 'display_name' => 'Kullanıcı Düzenle']);
        Permission::create(['name' => 'users.delete', 'guard_name' => 'web', 'display_name' => 'Kullanıcı Sil']);
    }
} 