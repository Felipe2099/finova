<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        // Gelir Kategorileri
        $incomeCategories = [
            ['name' => 'Maaş', 'color' => '#22c55e'],
            ['name' => 'Freelance', 'color' => '#3b82f6'],
            ['name' => 'Yatırım Gelirleri', 'color' => '#f59e0b'],
            ['name' => 'Kira Geliri', 'color' => '#8b5cf6'],
            ['name' => 'Diğer Gelirler', 'color' => '#64748b'],
        ];

        foreach ($incomeCategories as $category) {
            Category::create([
                'user_id' => $user->id,
                'type' => 'income',
                'name' => $category['name'],
                'color' => $category['color'],
                'status' => true,
            ]);
        }

        // Gider Kategorileri
        $expenseCategories = [
            ['name' => 'Kira', 'color' => '#ef4444'],
            ['name' => 'Market', 'color' => '#f97316'],
            ['name' => 'Faturalar', 'color' => '#06b6d4'],
            ['name' => 'Ulaşım', 'color' => '#6366f1'],
            ['name' => 'Sağlık', 'color' => '#ec4899'],
            ['name' => 'Eğitim', 'color' => '#14b8a6'],
            ['name' => 'Eğlence', 'color' => '#f43f5e'],
            ['name' => 'Diğer Giderler', 'color' => '#64748b'],
        ];

        foreach ($expenseCategories as $category) {
            Category::create([
                'user_id' => $user->id,
                'type' => 'expense',
                'name' => $category['name'],
                'color' => $category['color'],
                'status' => true,
            ]);
        }
    }
} 