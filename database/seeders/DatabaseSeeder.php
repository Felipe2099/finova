<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'phone' => '+905555555555',
            'password' => 'admin123',
            'has_commission' => true,
            'commission_rate' => 10,
            'status' => true,
            'remember_token' => Str::random(10),
        ]);

        $this->call([
            PermissionSeeder::class,
            AdminUserSeeder::class,
            CategorySeeder::class,
            DemoDataSeeder::class
        ]);
    }
}