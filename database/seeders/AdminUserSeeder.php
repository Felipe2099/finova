<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Mevcut rolleri ve izinleri temizle
        Schema::disableForeignKeyConstraints();
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('roles')->truncate();
        Schema::enableForeignKeyConstraints();

        // Varsayılan takım ID'si (ana şirket/organizasyon)
        $defaultTeamId = 1;

        // Super Admin rolü oluştur
        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'team_id' => $defaultTeamId
        ]);
        $superAdminRole->syncPermissions(Permission::all());

        // Admin rolü oluştur
        $adminRole = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
            'team_id' => $defaultTeamId
        ]);
        $adminRole->syncPermissions(Permission::whereNotIn('name', [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete'
        ])->get());

        // Manager rolü oluştur
        $managerRole = Role::create([
            'name' => 'manager',
            'guard_name' => 'web',
            'team_id' => $defaultTeamId
        ]);
        $managerRole->syncPermissions([
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'transactions.view', 'transactions.create', 'transactions.edit', 'transactions.delete',
            'reports.cash_flow', 'reports.category_analysis'
        ]);

        // Staff rolü oluştur
        $staffRole = Role::create([
            'name' => 'staff',
            'guard_name' => 'web',
            'team_id' => $defaultTeamId
        ]);
        $staffRole->syncPermissions([
            'customers.view', 'customers.create', 'customers.edit',
            'transactions.view', 'transactions.create',
            'reports.cash_flow'
        ]);

        // Accountant rolü oluştur
        $accountantRole = Role::create([
            'name' => 'accountant',
            'guard_name' => 'web',
            'team_id' => $defaultTeamId
        ]);
        $accountantRole->syncPermissions([
            'transactions.view', 'transactions.create', 'transactions.edit', 'transactions.delete',
            'reports.cash_flow', 'reports.category_analysis',
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete'
        ]);

        // Admin kullanıcısına Super Admin rolünü ata
        $admin = User::where('email', 'admin@admin.com')->first();
        if ($admin) {
            DB::table('model_has_roles')->insert([
                'role_id' => $superAdminRole->id,
                'model_type' => User::class,
                'model_id' => $admin->id,
                'team_id' => $defaultTeamId
            ]);
        }
    }
} 