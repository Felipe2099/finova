<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class DemoDataSeeder extends Seeder
{
    private $faker;
    private $admin;
    private $employee;
    private $startDate;
    private $endDate;

    public function __construct()
    {
        $this->faker = Faker::create('tr_TR');
        $this->startDate = Carbon::create(2023, 1, 1);
        $this->endDate = Carbon::now();
    }

    public function run(): void
    {
        $this->admin = User::where('email', 'admin@admin.com')->first();
        if (!$this->admin) {
            throw new \RuntimeException('Admin kullanıcısı bulunamadı. Önce UserSeeder çalıştırılmalı.');
        }

        $this->employee = User::where('email', 'test@test.com')->first();
        if (!$this->employee) {
            throw new \RuntimeException('Test kullanıcısı bulunamadı. Önce UserSeeder çalıştırılmalı.');
        }

        // For Admin, create all data
        $this->createCustomerGroups($this->admin);
        $this->createCategories($this->admin);
        $this->createCustomersAndLeads($this->admin);
        $this->createCustomerNotes($this->admin);
        $this->createCustomerAgreements($this->admin);
        $this->createAccounts($this->admin);
        $this->createLoans($this->admin);
        $this->createSavingsAndInvestments($this->admin);
        $this->createProjects($this->admin);
        $this->createTransactions($this->admin);

        // For Employee, only create customer management and transactions
        // Use admin's created accounts, categories, and groups
        $this->createCustomersAndLeads($this->employee); // Employee creates own customers
        $this->createCustomerNotes($this->employee);   // Employee creates own notes
        $this->createTransactions($this->employee);    // Employee creates transactions for own customers (using admin's accounts/categories)
        $this->createCommissionPayouts($this->employee); // Create commission payouts for employee
    
    }

    private function createCategories(User $user): void
    {
        // Simplified category structure (5-6 Income, 5-6 Expense)
        $incomeCategories = [
            ['name' => 'Hizmet Geliri', 'type' => 'income'],
            ['name' => 'Satış Geliri', 'type' => 'income'],
            ['name' => 'Abonelik Geliri', 'type' => 'income'],
            ['name' => 'Komisyon Geliri', 'type' => 'income'],
            ['name' => 'Faiz Geliri', 'type' => 'income'],
            ['name' => 'Diğer Gelirler', 'type' => 'income'],
        ];

        $expenseCategories = [
            ['name' => 'Ofis Giderleri', 'type' => 'expense'], // Rent, Bills, Materials, etc.
            ['name' => 'Personel Giderleri', 'type' => 'expense'],
            ['name' => 'Yazılım & Abonelikler', 'type' => 'expense'],
            ['name' => 'Pazarlama & Reklam Giderleri', 'type' => 'expense'],
            ['name' => 'Banka & Finansman Giderleri', 'type' => 'expense'],
            ['name' => 'Diğer Giderler', 'type' => 'expense'],
        ];

        foreach (array_merge($incomeCategories, $expenseCategories) as $category) {
            Category::create([
                'name' => $category['name'],
                'type' => $category['type'],
                'user_id' => $user->id,
            ]);
        }
    }

    private function createCustomerGroups(User $user): void
    {
        $groups = [
            [
                'name' => 'Kurumsal Müşteriler',
                'description' => 'Şirketler ve kurumsal müşteriler',
            ],
            [
                'name' => 'Bireysel Müşteriler',
                'description' => 'Bireysel müşteriler',
            ],
            [
                'name' => 'E-ticaret Müşterileri',
                'description' => 'Online satış platformlarından gelen müşteriler',
            ],
            [
                'name' => 'Yurtdışı Müşteriler',
                'description' => 'Yurtdışı müşteriler',
            ],
        ];
        
        foreach ($groups as $group) {
            CustomerGroup::create([
                'name' => $group['name'],
                'description' => $group['description'],
                'user_id' => $user->id,
            ]);
        }
    }

    private function createCustomersAndLeads(User $user): void
    {
        // First make sure groups exist
        // Always use admin user's customer groups
        $groups = CustomerGroup::where('user_id', $this->admin->id)->get();
        if ($groups->isEmpty()) {
            throw new \RuntimeException('Müşteri grupları bulunamadı. Önce createCustomerGroups çalıştırılmalı.');
        }

        // Create customers for last 1 year
        $startDate = Carbon::now()->subYear();
        $currentDate = $startDate->copy();
        
        while ($currentDate <= Carbon::now()) {
            // Create customers for last 1 year
            $monthlyCustomerCount = $this->faker->numberBetween(5, 10);
            
            for ($i = 0; $i < $monthlyCustomerCount; $i++) {
                $group = $groups->random(); // Random group selection
                $createdAt = $currentDate->copy()->addDays($this->faker->numberBetween(1, 28));
                
                $isCompany = $this->faker->boolean(70);
                Customer::create([
                    'name' => $isCompany ? $this->faker->company : $this->faker->name,
                    'type' => $isCompany ? 'corporate' : 'individual',
                    'tax_number' => $this->faker->numerify('##########'),
                    'tax_office' => $this->faker->city,
                    'email' => $this->faker->companyEmail,
                    'phone' => $this->faker->phoneNumber,
                    'address' => $this->faker->address,
                    'city' => $this->faker->city,
                    'district' => $this->faker->city,
                    'description' => $this->faker->sentence,
                    'status' => true, // All customers are active
                    'customer_group_id' => $group->id,
                    'user_id' => $user->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            // Leads only for admin
            if ($user->hasRole('admin')) {
                $monthlyLeadCount = $this->faker->numberBetween(2, 4);
                
                for ($i = 0; $i < $monthlyLeadCount; $i++) {
                    $createdAt = $currentDate->copy()->addDays($this->faker->numberBetween(1, 28));
                    $nextContactDate = $createdAt->copy()->addDays($this->faker->numberBetween(1, 30));
                    
                    Lead::create([
                        'name' => $this->faker->company,
                        'type' => 'corporate',
                        'email' => $this->faker->companyEmail,
                        'phone' => $this->faker->phoneNumber,
                        'address' => $this->faker->address,
                        'city' => $this->faker->city,
                        'district' => $this->faker->city,
                        'source' => $this->faker->randomElement(['website', 'referral', 'social_media', 'other']),
                        'status' => $this->faker->randomElement(['new', 'contacted', 'negotiating', 'converted', 'lost']),
                        'last_contact_date' => $createdAt,
                        'next_contact_date' => $nextContactDate,
                        'notes' => $this->faker->paragraph,
                        'user_id' => $user->id,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }
            }

            $currentDate->addMonth();
        }
    }

    private function createAccounts(User $user): void
    {
        $accounts = [
            [
                'name' => 'Ana Banka Hesabı',
                'type' => Account::TYPE_BANK_ACCOUNT,
                'currency' => 'TRY',
                'balance' => 50000,
                'details' => [
                    'bank_name' => 'İş Bankası',
                    'branch' => 'Merkez',
                    'account_no' => '1234567890',
                    'iban' => 'TR33 0006 4000 0011 2345 6789 01'
                ]
            ],
            [
                'name' => 'USD Hesabı',
                'type' => Account::TYPE_BANK_ACCOUNT,
                'currency' => 'USD',
                'balance' => 5000,
                'details' => [
                    'bank_name' => 'Akbank',
                    'branch' => 'Merkez',
                    'account_no' => '1234567891',
                    'iban' => 'TR33 0006 4000 0011 2345 6789 02'
                ]
            ],
            [
                'name' => 'EUR Hesabı',
                'type' => Account::TYPE_BANK_ACCOUNT,
                'currency' => 'EUR',
                'balance' => 3000,
                'details' => [
                    'bank_name' => 'Yapı Kredi',
                    'branch' => 'Merkez',
                    'account_no' => '1234567892',
                    'iban' => 'TR33 0006 4000 0011 2345 6789 03'
                ]
            ],
            [
                'name' => 'Maximum Kart',
                'type' => Account::TYPE_CREDIT_CARD,
                'currency' => 'TRY',
                'balance' => 15000,
                'details' => [
                    'bank_name' => 'İş Bankası',
                    'credit_limit' => 20000,
                    'statement_day' => 15,
                    'current_debt' => 15000
                ]
            ],
            [
                'name' => 'Binance BTC Cüzdanı',
                'type' => Account::TYPE_CRYPTO_WALLET,
                'currency' => 'USD',
                'balance' => 1000,
                'details' => [
                    'platform' => 'Binance',
                    'wallet_address' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',

                ]
            ],
            [
                'name' => 'PayTR Sanal POS',
                'type' => Account::TYPE_VIRTUAL_POS,
                'currency' => 'TRY',
                'balance' => 2575,
                'details' => [
                    'provider' => 'PayTR',
                ]
            ],
            [
                'name' => 'Nakit Kasa',
                'type' => Account::TYPE_CASH,
                'currency' => 'TRY',
                'balance' => 5000,
            ],
        ];

        foreach ($accounts as $account) {
            Account::create([
                'name' => $account['name'],
                'type' => $account['type'],
                'currency' => $account['currency'],
                'balance' => $account['balance'],
                'details' => $account['details'] ?? null,
                'user_id' => $user->id,
            ]);
        }
    }

    private function createProjects(User $user): void
    {
        // Create single project
        $project = Project::create([
            'name' => 'Müşteri Yönetim Sistemi',
            'description' => 'Müşteri ilişkileri ve satış süreçlerinin yönetimi için yazılım projesi',
            'status' => 'active',
            'view_type' => 'list',
            'created_by' => $user->id,
        ]);

        // Create main board


        // Create task lists
        $lists = [
            ['name' => 'Bekliyor', 'order' => 1],
            ['name' => 'İşlemde', 'order' => 2],
            ['name' => 'Test Ediliyor', 'order' => 3],
            ['name' => 'Tamamlandı', 'order' => 4]
        ];

        $taskLists = [];
        foreach ($lists as $list) {
            $taskLists[$list['name']] = \App\Models\TaskList::create([
                'board_id' => 1,
                'name' => $list['name'],
                'order' => $list['order']
            ]);
        }

        // In progress tasks
        $tasks = [
            [
                'list' => 'İşlemde',
                'tasks' => [
                    [
                        'title' => 'Müşteri raporlama sistemi geliştirmesi',
                        'content' => 'Müşteri bazlı gelir ve aktivite raporlarının oluşturulması',
                        'priority' => 'high',
                        'due_date' => now()->addDays(5),
                    ],
                    [
                        'title' => 'E-posta bildirim sistemi entegrasyonu',
                        'content' => 'Önemli müşteri aktiviteleri için otomatik e-posta bildirimleri',
                        'priority' => 'medium',
                        'due_date' => now()->addDays(7),
                    ]
                ]
            ],
            [
                'list' => 'Test Ediliyor',
                'tasks' => [
                    [
                        'title' => 'Müşteri portalı arayüz güncellemesi',
                        'content' => 'Yeni tasarım ve kullanıcı deneyimi iyileştirmeleri',
                        'priority' => 'medium',
                        'due_date' => now()->addDays(3),
                    ],
                    [
                        'title' => 'Tahsilat takip modülü',
                        'content' => 'Müşteri ödemelerinin takibi ve raporlanması',
                        'priority' => 'high',
                        'due_date' => now()->addDays(2),
                    ]
                ]
            ],
            [
                'list' => 'Tamamlandı',
                'tasks' => [
                    [
                        'title' => 'Müşteri veri tabanı optimizasyonu',
                        'content' => 'Performans iyileştirmeleri ve indeksleme',
                        'priority' => 'high',
                        'due_date' => now()->subDays(2),
                    ],
                    [
                        'title' => 'Yetkilendirme sistemi güncellemesi',
                        'content' => 'Rol bazlı erişim kontrolü ve güvenlik güncellemeleri',
                        'priority' => 'high',
                        'due_date' => now()->subDays(1),
                    ]
                ]
            ]
        ];

        // Create tasks
        foreach ($tasks as $listTasks) {
            $list = $taskLists[$listTasks['list']];
            foreach ($listTasks['tasks'] as $index => $task) {
                \App\Models\Task::create([
                    'task_list_id' => $list->id,
                    'title' => $task['title'],
                    'content' => $task['content'],
                    'priority' => $task['priority'],
                    'due_date' => $task['due_date'],
                    'order' => $index + 1,
                    'assigned_to' => $user->id
                ]);
            }
        }
    }

    private function createSavingsAndInvestments(User $user): void
    {
        // Only 2 savings plans
        $savingsPlans = [
            [
                'goal_name' => 'Acil Durum Fonu',
                'target_amount' => 50000,
                'saved_amount' => 35000,
                'target_date' => Carbon::now()->addMonths(6),
                'status' => 'active'
            ],
            [
                'goal_name' => 'Ofis Taşınma Fonu',
                'target_amount' => 100000,
                'saved_amount' => 25000,
                'target_date' => Carbon::now()->addYear(),
                'status' => 'active'
            ]
        ];

        foreach ($savingsPlans as $plan) {
            \App\Models\SavingsPlan::create([
                'user_id' => $user->id,
                'goal_name' => $plan['goal_name'],
                'target_amount' => $plan['target_amount'],
                'saved_amount' => $plan['saved_amount'],
                'target_date' => $plan['target_date'],
                'status' => $plan['status']
            ]);
        }

        // Only 2 investment plans
        $investmentPlans = [
            [
                'investment_name' => 'Bitcoin Yatırımı',
                'invested_amount' => 20000,
                'current_value' => 25000,
                'investment_type' => 'crypto',
                'investment_date' => Carbon::now()->subMonths(3)
            ],
            [
                'investment_name' => 'Hisse Senedi Portföyü',
                'invested_amount' => 50000,
                'current_value' => 55000,
                'investment_type' => 'stocks',
                'investment_date' => Carbon::now()->subMonths(6)
            ]
        ];

        foreach ($investmentPlans as $plan) {
            \App\Models\InvestmentPlan::create([
                'user_id' => $user->id,
                'investment_name' => $plan['investment_name'],
                'invested_amount' => $plan['invested_amount'],
                'current_value' => $plan['current_value'],
                'investment_type' => $plan['investment_type'],
                'investment_date' => $plan['investment_date']
            ]);
        }
    }

    private function createLoans(User $user): void
    {
        // Loans only for admin
        if (!$user->hasRole('admin')) {
            return;
        }

        $loans = [
            [
                'bank_name' => 'İş Bankası',
                'loan_type' => 'business',
                'amount' => 100000,
                'installments' => 24,
                'monthly_payment' => 5000,
                'remaining_installments' => 18,
                'start_date' => Carbon::now()->subMonths(6),
            ]
        ];

        foreach ($loans as $loan) {
            $startDate = $loan['start_date'];
            $monthlyPayment = $loan['monthly_payment'];
            $remainingInstallments = $loan['remaining_installments'];

            \App\Models\Loan::create([
                'user_id' => $user->id,
                'bank_name' => $loan['bank_name'],
                'loan_type' => $loan['loan_type'],
                'amount' => $loan['amount'],
                'monthly_payment' => $monthlyPayment,
                'installments' => $loan['installments'],
                'remaining_installments' => $remainingInstallments,
                'start_date' => $startDate,
                'next_payment_date' => Carbon::now()->addMonth()->startOfMonth(),
                'due_date' => $startDate->copy()->addMonths($loan['installments']),
                'remaining_amount' => $monthlyPayment * $remainingInstallments,
                'status' => 'pending', // Status as string 'active'
                'notes' => 'İşletme giderlerinin finansmanı için kullanılan kredi',
            ]);
        }
    }

    private function createTransactions(User $user): void
    {
        // Always use admin user's accounts and categories
        // --- Data Preparation (Before loop) ---

        // 1. Get required admin accounts and check
        $mainAccount = Account::where('user_id', $this->admin->id)->where('name', 'Ana Banka Hesabı')->first();
        $creditCard = Account::where('user_id', $this->admin->id)->where('type', Account::TYPE_CREDIT_CARD)->first();
        $cryptoWallet = Account::where('user_id', $this->admin->id)->where('type', Account::TYPE_CRYPTO_WALLET)->first();
        $virtualPos = Account::where('user_id', $this->admin->id)->where('type', Account::TYPE_VIRTUAL_POS)->first(); // İlk Sanal POS'u alalım
        $cashAccount = Account::where('user_id', $this->admin->id)->where('type', Account::TYPE_CASH)->first();

        if (!$mainAccount) {
            \Log::error('DemoDataSeeder: Admin için Ana Banka Hesabı bulunamadı!');
            return; // If main account doesn't exist, continue
        }

        // 2. Get admin category IDs and check
        $adminIncomeCategoryIds = Category::where('user_id', $this->admin->id)->where('type', 'income')->pluck('id')->toArray();
        $adminExpenseCategoryIds = Category::where('user_id', $this->admin->id)->where('type', 'expense')->pluck('id')->toArray();

        if (empty($adminIncomeCategoryIds) || empty($adminExpenseCategoryIds)) {
             \Log::error('DemoDataSeeder: Admin için gelir veya gider kategorileri bulunamadı!');
            return; // If categories don't exist, continue
        }

        // 3. Get current user's customer IDs and check
        $customerIds = Customer::where('user_id', $user->id)->pluck('id')->toArray();
        if (empty($customerIds) && $user->id !== $this->admin->id) {
             \Log::warning("DemoDataSeeder: Kullanıcı {$user->id} için müşteri bulunamadı, işlem oluşturulamıyor.");
             // For employee, if no customers, we cannot create income transactions, but other transactions (if any) can continue.
             // Therefore, we don't return here, we only skip the income loop.
        }

        // --- Transaction Creation Loops ---

        $startDate = $this->startDate->copy();
        $endDate = Carbon::now(); // Today's date
        $oneMonthAgo = Carbon::now()->subMonth();
        $createdIncomeTransactionIdsLastMonth = []; // To store income transaction IDs created last month

        while ($startDate <= $endDate) {

            // A. Credit Card Expense and Payment (Only logical for admin, but created for every user - for now keep it)
            if ($creditCard && $mainAccount) {
                $expenseCatId = !empty($adminExpenseCategoryIds) ? $adminExpenseCategoryIds[array_rand($adminExpenseCategoryIds)] : null;
                if ($expenseCatId) {
                    $ccAmount = $this->faker->numberBetween(1000, 9999);
                    Transaction::create([
                        'user_id' => $user->id,
                        'category_id' => $expenseCatId,
                        'source_account_id' => $creditCard->id,
                        'destination_account_id' => null,
                        'type' => 'expense',
                        'amount' => $ccAmount,
                        'currency' => 'TRY',
                        'exchange_rate' => 1,
                        'try_equivalent' => $ccAmount,
                        // Date should not be today
                        'date' => min($startDate->copy()->addDays($this->faker->numberBetween(1, 15)), $endDate),
                        'payment_method' => 'credit_card',
                        'description' => 'Aylık kredi kartı harcamaları',
                        'status' => 'completed',
                    ]);

                    // Payment (End of the month)
                    $paymentDate = $startDate->copy()->endOfMonth()->subDays(rand(0, 5));
                    if ($paymentDate <= $endDate) {
                         Transaction::create([
                            'user_id' => $user->id,
                            'source_account_id' => $mainAccount->id,
                            'destination_account_id' => $creditCard->id,
                            'type' => 'payment', // Payment type
                            'category_id' => null, // Payment category
                            'amount' => $ccAmount, // Amount of expense
                            'currency' => 'TRY',
                            'exchange_rate' => 1,
                            'try_equivalent' => $ccAmount,
                            // Date should not be today
                            'date' => min($paymentDate, $endDate),
                            'payment_method' => 'bank',
                            'description' => 'Kredi kartı ödemesi',
                            'status' => 'completed',
                        ]);
                    }
                }
            }

            // B. Customer Incomes (Only if customer exists)
            if (!empty($customerIds)) {
                $incomeCount = $this->faker->numberBetween(3, 8); // Less transactions
                for ($i = 0; $i < $incomeCount; $i++) {
                    $customerId = $customerIds[array_rand($customerIds)];
                    $incomeCatId = !empty($adminIncomeCategoryIds) ? $adminIncomeCategoryIds[array_rand($adminIncomeCategoryIds)] : null;

                    // If valid IDs exist, continue
                    if ($customerId && $incomeCatId) {
                        $amount = $this->faker->numberBetween(500, 6000);
                        $paymentMethodType = $this->faker->randomElement(['bank', 'virtual_pos', 'cash']);
                        // Date should not be today
                        $transactionDate = min($startDate->copy()->addDays($this->faker->numberBetween(1, 28)), $endDate);

                        // Determine accounts and payment method (more strict control)
                        $sourceAccount = null;
                        $destAccount = null;
                        $paymentMethod = null;
                        $description = null;

                        if ($paymentMethodType === 'virtual_pos') {
                            // Only if admin has Virtual POS and Main Account
                            if ($virtualPos && $mainAccount) {
                                $sourceAccount = $virtualPos;
                                $destAccount = $mainAccount;
                                $paymentMethod = 'virtual_pos';
                                $description = 'Sanal POS tahsilatı';
                            } else {
                                continue; // If required account doesn't exist, skip this transaction for this customer
                            }
                        } elseif ($paymentMethodType === 'cash') {
                             // Only if admin has Cash Account
                            if ($cashAccount) {
                                $sourceAccount = $cashAccount;
                                $destAccount = null;
                                $paymentMethod = 'cash';
                                $description = 'Nakit tahsilat';
                            } else {
                                continue; // If required account doesn't exist, skip this transaction for this customer
                            }
                        } elseif ($paymentMethodType === 'bank') {
                             // Only if admin has Main Account
                             if ($mainAccount) {
                                $sourceAccount = $mainAccount;
                                $destAccount = null;
                                $paymentMethod = 'bank';
                                $description = 'Havale/EFT tahsilatı';
                            } else {
                                // If main account doesn't exist, we already returned in the beginning, but skip anyway
                                continue;
                            }
                        } else {
                             // Unexpected paymentMethodType, skip this transaction
                             \Log::warning("DemoDataSeeder: Beklenmedik paymentMethodType: {$paymentMethodType}");
                             continue;
                        }
                        // If we get here, $sourceAccount and $paymentMethod should be valid

                        // If source account is valid, create transaction
                        if ($sourceAccount) {
                            $transactionData = [
                                'user_id' => $user->id,
                                'category_id' => $incomeCatId,
                                'customer_id' => $customerId,
                                'source_account_id' => $sourceAccount->id,
                                'destination_account_id' => $destAccount ? $destAccount->id : null,
                                'type' => 'income',
                                'amount' => $amount,
                                'currency' => $sourceAccount->currency === 'USD' ? 'USD' : 'TRY', // Currency based on account
                                'exchange_rate' => $sourceAccount->currency === 'USD' ? 32 : 1, // Simple exchange rate
                                'try_equivalent' => $sourceAccount->currency === 'USD' ? $amount * 32 : $amount,
                                'date' => $transactionDate,
                                'payment_method' => $paymentMethod,
                                'description' => $description,
                                'status' => 'completed',
                                'is_subscription' => false,
                            ];

                            // For subscription, store ID (will be processed later)
                            if ($transactionDate >= $oneMonthAgo) {
                                $createdIncomeTransactionIdsLastMonth[] = Transaction::create($transactionData)->id; // Get ID and add to array
                                continue; // Skip this transaction to avoid creating it again
                            }

                            // Create normal transaction (not subscription and not old date)
                            Transaction::create($transactionData);
                        }
                    }
                }
            }

            // C. Fixed Expenses (Only for admin)
            if ($user->hasRole('admin') && $mainAccount) {
                 $expenseCatId = !empty($adminExpenseCategoryIds) ? $adminExpenseCategoryIds[array_rand($adminExpenseCategoryIds)] : null;
                 if($expenseCatId) {
                    $expenses = [ 
                    ['name' => 'Ofis kirası', 'amount' => 15000],
                     ['name' => 'Elektrik faturası', 'amount' => [400, 800]],
                     ['name' => 'Su faturası', 'amount' => [200, 400]],
                     ['name' => 'İnternet faturası', 'amount' => 500],
                     ['name' => 'Telefon faturası', 'amount' => [300, 600]],
                     ['name' => 'Temizlik hizmeti', 'amount' => 2000],
                    ]; // Array closed here

                    foreach ($expenses as $expense) {
                        $amount = is_array($expense['amount'])
                            ? $this->faker->numberBetween($expense['amount'][0], $expense['amount'][1])
                            : $expense['amount'];

                        Transaction::create([
                            'user_id' => $user->id,
                            'category_id' => $expenseCatId,
                            'source_account_id' => $mainAccount->id, // From main account
                            'destination_account_id' => null,
                            'type' => 'expense',
                            'amount' => $amount,
                            'currency' => 'TRY',
                            'exchange_rate' => 1,
                            'try_equivalent' => $amount,
                            // Date should not be today
                            'date' => min($startDate->copy()->addDays($this->faker->numberBetween(1, 28)), $endDate),
                            'payment_method' => 'bank',
                            'description' => $expense['name'],
                            'status' => 'completed'
                        ]);
                    }
                 }
            }

            $startDate->addMonth();
        } // while ($startDate <= $endDate) loop ends

        // --- Set Subscriptions (After loop) ---
        if (!empty($createdIncomeTransactionIdsLastMonth)) {
            $subscriptionCount = $this->faker->numberBetween(5, 10); // 5-10 subscriptions
            // Shuffle array and select desired number of IDs
            shuffle($createdIncomeTransactionIdsLastMonth);
            $subscriptionIds = array_slice($createdIncomeTransactionIdsLastMonth, 0, $subscriptionCount);

            if (!empty($subscriptionIds)) {
                // Seçilen işlemleri güncelle
                Transaction::whereIn('id', $subscriptionIds)->update([
                    'is_subscription' => true,
                    'subscription_period' => 'monthly',
                    'auto_renew' => true,
                    // next_payment_date should be calculated for each transaction separately
                    // but for simplicity, in the bulk update, set transaction date + 1 month
                    // For now, only update is_subscription, others can be set manually.
                    // 'next_payment_date' => DB::raw('DATE_ADD(date, INTERVAL 1 MONTH)'), // This line doesn't work directly + 1 month
                ]);

                // Update next_payment_date and description  
                $subscriptionsToUpdate = Transaction::whereIn('id', $subscriptionIds)->get();
                foreach ($subscriptionsToUpdate as $sub) {
                    $sub->update([
                        'next_payment_date' => Carbon::parse($sub->date)->addMonth(),
                        'description' => $sub->description . ' (Aylık Abonelik)'
                    ]);
                }
            }
        }

        // D. Crypto Transactions (Only for admin and if accounts exist)
        if ($user->hasRole('admin') && $cryptoWallet && $mainAccount) {
            $cryptoTransactions = [ // Array definition starts here
                 ['type' => 'expense', 'description' => 'BTC Alım', 'amount' => 10000],
                ['type' => 'income', 'description' => 'BTC Satış', 'amount' => 12000],
                ['type' => 'expense', 'description' => 'ETH Alım', 'amount' => 5000],
                ['type' => 'income', 'description' => 'ETH Satış', 'amount' => 6000],
            ]; // Array closed here

            foreach ($cryptoTransactions as $index => $tx) {
                $date = Carbon::now()->subMonths($index + 1);
                $exchangeRate = $this->faker->randomFloat(2, 28, 32);
                $categoryId = null;
                if($tx['type'] === 'income' && !empty($adminIncomeCategoryIds)) {
                    $categoryId = $adminIncomeCategoryIds[array_rand($adminIncomeCategoryIds)];
                } elseif ($tx['type'] === 'expense' && !empty($adminExpenseCategoryIds)) {
                     $categoryId = $adminExpenseCategoryIds[array_rand($adminExpenseCategoryIds)];
                }

                if($categoryId) { // If category exists, continue
                    $transactionData = [
                        'user_id' => $user->id,
                        'category_id' => $categoryId,
                        'type' => $tx['type'],
                        'amount' => $tx['amount'],
                        'currency' => 'USD',
                        'exchange_rate' => $exchangeRate,
                        'try_equivalent' => $tx['amount'] * $exchangeRate,
                        'date' => $date,
                        'payment_method' => 'crypto',
                        'description' => $tx['description'],
                        'status' => 'completed'
                    ];

                    if ($tx['type'] === 'income') { // Sale: Crypto -> Bank
                        $transactionData['source_account_id'] = $cryptoWallet->id;
                        $transactionData['destination_account_id'] = $mainAccount->id;
                    } else { // Purchase: Bank -> Crypto
                        $transactionData['source_account_id'] = $mainAccount->id;
                        $transactionData['destination_account_id'] = $cryptoWallet->id;
                    }
                    Transaction::create($transactionData);
                }
            }
        }
    }

    private function getPaymentMethod(string $accountType): string
    {
        return match ($accountType) {
            Account::TYPE_BANK_ACCOUNT => 'bank',
            Account::TYPE_CREDIT_CARD => 'credit_card',
            Account::TYPE_CRYPTO_WALLET => 'crypto',
            Account::TYPE_VIRTUAL_POS => 'virtual_pos',
            Account::TYPE_CASH => 'cash',
            default => 'bank',
        };
    }

    private function createCustomerNotes(User $user): void
    {
        $customers = \App\Models\Customer::where('user_id', $user->id)->get();
        $noteTypes = ['note', 'call', 'meeting', 'email', 'other'];

        // Meaningful note contents
        $noteContents = [
            'note' => [
                'Müşteri ile genel durum değerlendirmesi yapıldı.',
                'Yeni proje teklifi hakkında bilgi verildi.',
                'Ödeme hatırlatması yapılması gerekiyor.',
                'Müşteri memnuniyeti anketi gönderildi.',
                'Rakip analizi hakkında notlar alındı.',
            ],
            'call' => [
                'Müşteri ile telefon görüşmesi yapıldı, teklif detayları konuşuldu.',
                'Destek talebi için arandı, sorun çözüldü.',
                'Yeni kampanya hakkında bilgi vermek için arandı.',
                'Randevu teyidi için arandı.',
                'Tahsilat durumu hakkında görüşüldü.',
            ],
            'meeting' => [
                'Müşteri ofisinde toplantı yapıldı, proje sunumu gerçekleştirildi.',
                'Online toplantı ile demo gösterimi yapıldı.',
                'Strateji toplantısı yapıldı, sonraki adımlar belirlendi.',
                'Yıllık değerlendirme toplantısı gerçekleştirildi.',
                'Yeni iş birliği olanakları üzerine toplantı yapıldı.',
            ],
            'email' => [
                'Teklif e-posta ile gönderildi.',
                'Toplantı özeti e-posta ile paylaşıldı.',
                'Sözleşme taslağı e-posta ile iletildi.',
                'Bilgilendirme e-postası gönderildi.',
                'Destek talebi yanıtı e-posta ile gönderildi.',
            ],
            'other' => [
                'Müşteri etkinliğine katılım sağlandı.',
                'Sosyal medya üzerinden etkileşim kuruldu.',
                'Referans kontrolü yapıldı.',
                'Fuar ziyareti sırasında görüşüldü.',
                'Genel araştırma notları.',
            ],
        ];
        
        foreach ($customers as $customer) {
            // Create 3-8 notes for each customer
            $noteCount = $this->faker->numberBetween(3, 5);
            
            for ($i = 0; $i < $noteCount; $i++) {
                $type = $this->faker->randomElement($noteTypes);
                $activityDate = $this->faker->boolean(70) 
                    ? $this->faker->dateTimeBetween('-6 months', 'now')
                    : $this->faker->dateTimeBetween('now', '+1 month');

                // Select meaningful content based on type
                $content = isset($noteContents[$type]) && !empty($noteContents[$type])
                    ? $this->faker->randomElement($noteContents[$type])
                    : $this->faker->sentence; // If type has no content, random sentence

                \App\Models\CustomerNote::create([
                    'customer_id' => $customer->id,
                    'user_id' => $user->id,
                    'assigned_user_id' => $this->faker->boolean(30) ? $user->id : null,
                    'content' => $content, // Meaningful content
                    'type' => $type,
                    'activity_date' => $activityDate,
                ]);
            }
        }
    }

    private function createCustomerAgreements(User $user): void
    {
        $customers = \App\Models\Customer::where('user_id', $user->id)->get();
        
        foreach ($customers as $customer) {
            // Create 1-3 agreements for each customer
            if ($this->faker->boolean(70)) {
                $agreementCount = $this->faker->numberBetween(1, 3);
                
                for ($i = 0; $i < $agreementCount; $i++) {
                    $startDate = $this->faker->dateTimeBetween('-1 year', 'now');
                    $amount = $this->faker->numberBetween(5000, 50000);

                    \App\Models\CustomerAgreement::create([
                        'user_id' => $user->id,
                        'customer_id' => $customer->id,
                        'name' => $this->faker->randomElement([
                            'Aylık Bakım Anlaşması',
                            'Yazılım Geliştirme Projesi',
                            'Danışmanlık Hizmeti',
                            'Teknik Destek Paketi',
                            'SEO Hizmeti'
                        ]),
                        'description' => $this->faker->sentence(10),
                        'amount' => $amount,
                        'start_date' => $startDate,
                        'next_payment_date' => Carbon::parse($startDate)->addMonth(),
                        'status' => $this->faker->randomElement(['active', 'completed', 'cancelled'])
                    ]);
                }
            }
        }
    }


    private function createCommissionPayouts(User $employee): void // Change parameter name to employee
    {
        // Get required admin category and account for payment
        $adminExpenseCategory = Category::where('user_id', $this->admin->id)
            ->where('type', 'expense')
            ->where('name', 'Personel Giderleri')
            ->first();

        $adminMainAccount = Account::where('user_id', $this->admin->id)
            ->where('name', 'Ana Banka Hesabı')
            ->first();

        if (!$adminExpenseCategory || !$adminMainAccount) {
            \Log::warning('DemoDataSeeder: Komisyon ödemesi için gerekli admin kategorisi veya hesabı bulunamadı.');
            return;
        }

        // Target last two months
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();
        $previousMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $previousMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Get employee's commission rate (default %5)
        $commissionRate = $employee->commission_rate ? ($employee->commission_rate / 100) : 0.05;

        // 1. Previous Month Commission (Full Payment)
        $previousMonthCommission = Transaction::where('user_id', $employee->id)
            ->where('type', 'income')
            ->whereBetween('date', [$previousMonthStart, $previousMonthEnd])
            ->sum('amount') * $commissionRate;

        if ($previousMonthCommission > 0) {
            $paymentDate = $currentMonthStart->copy()->addDays(5); // Pay at the beginning of the month

            // CommissionPayout record (For employee)
            \App\Models\CommissionPayout::create([
                'user_id' => $employee->id,
                'amount' => $previousMonthCommission,
                'payment_date' => $paymentDate,
            ]);

            // Transaction record (Expense, For employee, From admin account)
            Transaction::create([
                'user_id' => $employee->id, // Expense for employee
                'type' => 'expense',
                'category_id' => $adminExpenseCategory->id, // Admin's Expense category
                'source_account_id' => $adminMainAccount->id, // Payment from admin's main account
                'destination_account_id' => null,
                'amount' => $previousMonthCommission,
                'currency' => 'TRY',
                'exchange_rate' => 1,
                'try_equivalent' => $previousMonthCommission,
                'date' => $paymentDate,
                'payment_method' => 'bank',
                'description' => $previousMonthStart->format('F Y') . ' dönemi komisyon ödemesi (Tamamı)',
                'status' => 'completed',
            ]);
        }

        // 2. Current Month Commission (Half Payment)
        $currentMonthCommission = Transaction::where('user_id', $employee->id)
            ->where('type', 'income')
            ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
            ->sum('amount') * $commissionRate;

        if ($currentMonthCommission > 0) {
            $amountToPay = round($currentMonthCommission / 2, 2); // Half of the commission
            $paymentDate = $currentMonthEnd->copy()->addDays(5); // Pay at the beginning of the next month (or end of the month)

             // CommissionPayout record (For employee, half amount)
            \App\Models\CommissionPayout::create([
                'user_id' => $employee->id,
                'amount' => $amountToPay, // Only half of the amount paid
                'payment_date' => $paymentDate,
                // Note: Maybe add total earned commission here as well
            ]);

            // Transaction record (Expense, For employee, From admin account, half amount)
            Transaction::create([
                'user_id' => $employee->id,
                'type' => 'expense',
                'category_id' => $adminExpenseCategory->id,
                'source_account_id' => $adminMainAccount->id,
                'destination_account_id' => null,
                'amount' => $amountToPay,
                'currency' => 'TRY',
                'exchange_rate' => 1,
                'try_equivalent' => $amountToPay,
                'date' => $paymentDate,
                'payment_method' => 'bank',
                'description' => $currentMonthStart->format('F Y') . ' dönemi komisyon ödemesi (Yarısı)',
                'status' => 'completed',
            ]);
        }
    }
}
