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

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'admin@admin.com')->first();
        
        // Müşteri grupları oluştur
        $this->createCustomerGroups($user);
        
        // Müşteriler ve potansiyel müşteriler oluştur
        $this->createCustomersAndLeads($user);
        
        // Hesaplar oluştur
        $this->createAccounts($user);
        
        // Projeler oluştur
        $this->createProjects($user);
        
        // Gelir ve giderler oluştur
        $this->createTransactions($user);
    }
    
    /**
     * Müşteri grupları oluştur
     */
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
    
    /**
     * Müşteriler ve potansiyel müşteriler oluştur
     */
    private function createCustomersAndLeads(User $user): void
    {
        $corporateGroup = CustomerGroup::where('name', 'Kurumsal Müşteriler')->first();
        $individualGroup = CustomerGroup::where('name', 'Bireysel Müşteriler')->first();
        $ecommerceGroup = CustomerGroup::where('name', 'E-ticaret Müşterileri')->first();
        $internationalGroup = CustomerGroup::where('name', 'Yurtdışı Müşteriler')->first();
        
        // Kurumsal Müşteriler
        $corporateCustomers = [
            [
                'name' => 'Teknoloji A.Ş.',
                'type' => 'corporate',
                'tax_number' => '1234567890',
                'tax_office' => 'İstanbul',
                'email' => 'info@teknoloji.com',
                'phone' => '0212 555 1234',
                'address' => 'Levent Mah. İş Kulesi No:1',
                'city' => 'İstanbul',
                'district' => 'Levent',
                'description' => 'Yazılım ve teknoloji çözümleri sunan firma',
                'status' => true,
                'customer_group_id' => $corporateGroup->id,
            ],
            [
                'name' => 'İnşaat Ltd. Şti.',
                'type' => 'corporate',
                'tax_number' => '2345678901',
                'tax_office' => 'Ankara',
                'email' => 'info@insaat.com',
                'phone' => '0312 444 5678',
                'address' => 'Çankaya Cad. No:42',
                'city' => 'Ankara',
                'district' => 'Çankaya',
                'description' => 'İnşaat ve taahhüt işleri yapan firma',
                'status' => true,
                'customer_group_id' => $corporateGroup->id,
            ],
            [
                'name' => 'Mobilya Sanayi A.Ş.',
                'type' => 'corporate',
                'tax_number' => '3456789012',
                'tax_office' => 'İzmir',
                'email' => 'satis@mobilya.com',
                'phone' => '0232 333 9876',
                'address' => 'Organize Sanayi Bölgesi No:15',
                'city' => 'İzmir',
                'district' => 'Bornova',
                'description' => 'Ofis ve ev mobilyaları üreten firma',
                'status' => true,
                'customer_group_id' => $corporateGroup->id,
            ],
        ];
        
        // Bireysel Müşteriler
        $individualCustomers = [
            [
                'name' => 'Ahmet Yılmaz',
                'type' => 'individual',
                'tax_number' => '12345678901',
                'tax_office' => 'İstanbul',
                'email' => 'ahmet@gmail.com',
                'phone' => '0532 123 4567',
                'address' => 'Bağdat Cad. No:123',
                'city' => 'İstanbul',
                'district' => 'Kadıköy',
                'description' => 'Düzenli alışveriş yapan müşteri',
                'status' => true,
                'customer_group_id' => $individualGroup->id,
            ],
            [
                'name' => 'Ayşe Demir',
                'type' => 'individual',
                'tax_number' => '23456789012',
                'tax_office' => 'İstanbul',
                'email' => 'ayse@gmail.com',
                'phone' => '0533 234 5678',
                'address' => 'Nispetiye Cad. No:45',
                'city' => 'İstanbul',
                'district' => 'Etiler',
                'description' => 'Premium hizmet alan müşteri',
                'status' => true,
                'customer_group_id' => $individualGroup->id,
            ],
        ];
        
        // E-ticaret Müşterileri
        $ecommerceCustomers = [
            [
                'name' => 'Mehmet Kaya',
                'type' => 'individual',
                'tax_number' => '34567890123',
                'tax_office' => 'Bursa',
                'email' => 'mehmet@hotmail.com',
                'phone' => '0535 345 6789',
                'address' => 'Cumhuriyet Mah. No:78',
                'city' => 'Bursa',
                'district' => 'Nilüfer',
                'description' => 'Online mağazadan alışveriş yapan müşteri',
                'status' => true,
                'customer_group_id' => $ecommerceGroup->id,
            ],
            [
                'name' => 'Zeynep Öztürk',
                'type' => 'individual',
                'tax_number' => '45678901234',
                'tax_office' => 'Antalya',
                'email' => 'zeynep@gmail.com',
                'phone' => '0536 456 7890',
                'address' => 'Lara Cad. No:56',
                'city' => 'Antalya',
                'district' => 'Lara',
                'description' => 'Düzenli online alışveriş yapan müşteri',
                'status' => true,
                'customer_group_id' => $ecommerceGroup->id,
            ],
        ];
        
        // Yurtdışı Müşteriler
        $internationalCustomers = [
            [
                'name' => 'Global Tech GmbH',
                'type' => 'corporate',
                'tax_number' => 'DE123456789',
                'tax_office' => 'Berlin',
                'email' => 'contact@globaltech.de',
                'phone' => '+49 30 12345678',
                'address' => 'Berliner Str. 123',
                'city' => 'Berlin',
                'district' => 'Mitte',
                'description' => 'Alman teknoloji şirketi',
                'status' => true,
                'customer_group_id' => $internationalGroup->id,
            ],
            [
                'name' => 'American Trade Inc.',
                'type' => 'corporate',
                'tax_number' => 'US987654321',
                'tax_office' => 'New York',
                'email' => 'info@americantrade.com',
                'phone' => '+1 212 9876543',
                'address' => '5th Avenue 789',
                'city' => 'New York',
                'district' => 'Manhattan',
                'description' => 'Amerikan ticaret şirketi',
                'status' => true,
                'customer_group_id' => $internationalGroup->id,
            ],
        ];
        
        // Potansiyel Müşteriler (Leads)
        $leads = [
            [
                'name' => 'Dijital Medya Ltd.',
                'type' => 'corporate',
                'email' => 'info@dijitalmedya.com',
                'phone' => '0216 789 1234',
                'address' => 'Ataşehir Bulvarı No:67',
                'city' => 'İstanbul',
                'district' => 'Ataşehir',
                'source' => 'website',
                'status' => 'new',
                'last_contact_date' => Carbon::now()->subDays(5),
                'next_contact_date' => Carbon::now()->addDays(2),
                'notes' => 'Web sitesi tasarımı ve dijital pazarlama hizmetleri ile ilgileniyor',
            ],
            [
                'name' => 'Organik Gıda A.Ş.',
                'type' => 'corporate',
                'email' => 'iletisim@organikgida.com',
                'phone' => '0224 567 8901',
                'address' => 'Yıldırım Cad. No:34',
                'city' => 'Bursa',
                'district' => 'Yıldırım',
                'source' => 'referral',
                'status' => 'contacted',
                'last_contact_date' => Carbon::now()->subDays(10),
                'next_contact_date' => Carbon::now()->addDays(5),
                'notes' => 'Organik gıda üretimi yapan firma, muhasebe yazılımı arıyor',
            ],
            [
                'name' => 'Fatma Şahin',
                'type' => 'individual',
                'email' => 'fatma@gmail.com',
                'phone' => '0537 567 8901',
                'address' => 'Bahçelievler Mah. No:23',
                'city' => 'İzmir',
                'district' => 'Karşıyaka',
                'source' => 'social_media',
                'status' => 'contacted',
                'last_contact_date' => Carbon::now()->subDays(3),
                'next_contact_date' => Carbon::now()->addDays(1),
                'notes' => 'Kişisel finansal danışmanlık hizmeti almak istiyor',
            ],
            [
                'name' => 'Eğitim Akademisi',
                'type' => 'corporate',
                'email' => 'bilgi@egitimakademisi.com',
                'phone' => '0312 678 9012',
                'address' => 'Kızılay Meydanı No:12',
                'city' => 'Ankara',
                'district' => 'Kızılay',
                'source' => 'other',
                'status' => 'negotiating',
                'last_contact_date' => Carbon::now()->subDays(7),
                'next_contact_date' => Carbon::now()->addDays(3),
                'notes' => 'Online eğitim platformu için yazılım çözümleri arıyor',
            ],
        ];
        
        // Müşterileri oluştur
        foreach (array_merge($corporateCustomers, $individualCustomers, $ecommerceCustomers, $internationalCustomers) as $customerData) {
            $customerData['user_id'] = $user->id;
            Customer::create($customerData);
        }
        
        // Potansiyel müşterileri oluştur
        foreach ($leads as $leadData) {
            $leadData['user_id'] = $user->id;
            Lead::create($leadData);
        }
    }
    
    /**
     * Hesaplar oluştur
     */
    private function createAccounts(User $user): void
    {
        $accounts = [
            // Banka Hesapları
            [
                'name' => 'İş Bankası Vadesiz TL',
                'type' => Account::TYPE_BANK_ACCOUNT,
                'currency' => 'TRY',
                'balance' => 15000.00,
                'details' => [
                    'bank_name' => 'İş Bankası',
                    'account_number' => '1234567890',
                    'iban' => 'TR123456789012345678901234',
                    'branch' => 'Levent Şubesi',
                ],
                'status' => true,
            ],
            [
                'name' => 'Garanti USD Hesabı',
                'type' => Account::TYPE_BANK_ACCOUNT,
                'currency' => 'USD',
                'balance' => 5000.00,
                'details' => [
                    'bank_name' => 'Garanti Bankası',
                    'account_number' => '0987654321',
                    'iban' => 'TR098765432109876543210987',
                    'branch' => 'Kadıköy Şubesi',
                ],
                'status' => true,
            ],
            
            // Kredi Kartları
            [
                'name' => 'Yapı Kredi Worldcard',
                'type' => Account::TYPE_CREDIT_CARD,
                'currency' => 'TRY',
                'balance' => -3500.00,
                'details' => [
                    'bank_name' => 'Yapı Kredi',
                    'card_number' => '5412********3456',
                    'expiry_date' => '12/27',
                    'credit_limit' => 20000.00,
                ],
                'status' => true,
            ],
            
            // Kripto Cüzdanı
            [
                'name' => 'USDT (Tether) Cüzdanı',
                'type' => Account::TYPE_CRYPTO_WALLET,
                'currency' => 'USD',
                'balance' => 1500.00,
                'details' => [
                    'wallet_address' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
                    'platform' => 'Binance',
                ],
                'status' => true,
            ],
            
            // Sanal POS
            [
                'name' => 'iyzico Sanal POS',
                'type' => Account::TYPE_VIRTUAL_POS,
                'currency' => 'TRY',
                'balance' => 7500.00,
                'details' => [
                    'provider' => 'iyzico',
                    'merchant_id' => 'MER123456',
                    'api_key' => 'api_key_123456',
                ],
                'status' => true,
            ],
            [
                'name' => 'PayTR Sanal POS',
                'type' => Account::TYPE_VIRTUAL_POS,
                'currency' => 'TRY',
                'balance' => 4200.00,
                'details' => [
                    'provider' => 'PayTR',
                    'merchant_id' => 'MER654321',
                    'api_key' => 'api_key_654321',
                ],
                'status' => true,
            ],
            
            // Nakit
            [
                'name' => 'Nakit Kasa',
                'type' => Account::TYPE_CASH,
                'currency' => 'TRY',
                'balance' => 2500.00,
                'details' => [],
                'status' => true,
            ],
        ];
        
        foreach ($accounts as $accountData) {
            $accountData['user_id'] = $user->id;
            Account::create($accountData);
        }
    }
    
    /**
     * Projeler oluştur
     */
    private function createProjects(User $user): void
    {
        $projects = [
            [
                'name' => 'Web Sitesi Yenileme',
                'description' => 'Kurumsal web sitesinin yenilenmesi projesi',
                'status' => 'active',
            ],
            [
                'name' => 'Mobil Uygulama Geliştirme',
                'description' => 'iOS ve Android için mobil uygulama geliştirme projesi',
                'status' => 'active',
            ],
            [
                'name' => 'E-ticaret Entegrasyonu',
                'description' => 'Mevcut sisteme e-ticaret modülü entegrasyonu',
                'status' => 'active',
            ],
            [
                'name' => 'Sosyal Medya Kampanyası',
                'description' => 'Yeni ürün lansmanı için sosyal medya kampanyası',
                'status' => 'completed',
            ],
        ];
        
        foreach ($projects as $projectData) {
            $projectData['created_by'] = $user->id;
            Project::create($projectData);
        }
    }
    
    /**
     * Gelir ve giderler oluştur
     */
    private function createTransactions(User $user): void
    {
        // Hesapları al
        $bankAccount = Account::where('name', 'İş Bankası Vadesiz TL')->first();
        $usdAccount = Account::where('name', 'Garanti USD Hesabı')->first();
        $creditCard = Account::where('name', 'Yapı Kredi Worldcard')->first();
        $cryptoWallet = Account::where('name', 'USDT (Tether) Cüzdanı')->first();
        $virtualPos1 = Account::where('name', 'iyzico Sanal POS')->first();
        $virtualPos2 = Account::where('name', 'PayTR Sanal POS')->first();
        $cashAccount = Account::where('name', 'Nakit Kasa')->first();
        
        // Müşterileri al
        $techCompany = Customer::where('name', 'Teknoloji A.Ş.')->first();
        $constructionCompany = Customer::where('name', 'İnşaat Ltd. Şti.')->first();
        $furnitureCompany = Customer::where('name', 'Mobilya Sanayi A.Ş.')->first();
        $ahmet = Customer::where('name', 'Ahmet Yılmaz')->first();
        $ayse = Customer::where('name', 'Ayşe Demir')->first();
        $mehmet = Customer::where('name', 'Mehmet Kaya')->first();
        $zeynep = Customer::where('name', 'Zeynep Öztürk')->first();
        $globalTech = Customer::where('name', 'Global Tech GmbH')->first();
        $americanTrade = Customer::where('name', 'American Trade Inc.')->first();
        
        // Kategorileri al
        $incomeCategories = Category::where('type', 'income')->get();
        $expenseCategories = Category::where('type', 'expense')->get();
        
        // Son 2 yıl için gelir ve giderler oluştur
        $startDate = Carbon::now()->subYears(2);
        $endDate = Carbon::now();
        
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            // Her ay için 5-10 gelir işlemi
            $incomeCount = rand(5, 10);
            for ($i = 0; $i < $incomeCount; $i++) {
                $transactionDate = clone $currentDate;
                $transactionDate->addDays(rand(0, 30));
                
                if ($transactionDate > $endDate) {
                    continue;
                }
                
                // Rastgele müşteri seç
                $customers = [$techCompany, $constructionCompany, $furnitureCompany, $ahmet, $ayse, $mehmet, $zeynep, $globalTech, $americanTrade];
                $customer = $customers[array_rand($customers)];
                
                // Rastgele hesap seç
                $accounts = [$bankAccount, $virtualPos1, $virtualPos2, $cashAccount];
                if ($customer === $globalTech || $customer === $americanTrade) {
                    $accounts[] = $usdAccount; // Yurtdışı müşteriler için USD hesabı da ekle
                }
                $account = $accounts[array_rand($accounts)];
                
                // Rastgele kategori seç
                $category = $incomeCategories->random();
                
                // Gelir miktarı belirle (müşteri tipine göre)
                $amount = 0;
                if ($customer->type === 'corporate') {
                    $amount = rand(1000, 10000) + (rand(0, 99) / 100);
                } else {
                    $amount = rand(100, 2000) + (rand(0, 99) / 100);
                }
                
                // USD hesabı için dolar cinsinden işlem
                if ($account === $usdAccount) {
                    $amount = $amount / 30; // Basit bir kur hesabı
                    $tryEquivalent = $amount * 30;
                } else {
                    $tryEquivalent = $amount;
                }
                
                // İşlemi oluştur
                $paymentMethod = Transaction::PAYMENT_METHOD_BANK;
                
                if ($account->type === Account::TYPE_CASH) {
                    $paymentMethod = Transaction::PAYMENT_METHOD_CASH;
                } elseif ($account->type === Account::TYPE_BANK_ACCOUNT) {
                    $paymentMethod = Transaction::PAYMENT_METHOD_BANK;
                } elseif ($account->type === Account::TYPE_VIRTUAL_POS) {
                    $paymentMethod = Transaction::PAYMENT_METHOD_VIRTUAL_POS;
                }
                
                Transaction::create([
                    'user_id' => $user->id,
                    'source_account_id' => $account->id,
                    'category_id' => $category->id,
                    'customer_id' => $customer->id,
                    'type' => Transaction::TYPE_INCOME,
                    'amount' => $amount,
                    'currency' => $account->currency,
                    'exchange_rate' => $account->currency === 'TRY' ? 1 : 30,
                    'try_equivalent' => $tryEquivalent,
                    'date' => $transactionDate,
                    'description' => $category->name . ' geliri - ' . $customer->name,
                    'payment_method' => $paymentMethod,
                    'status' => 'completed',
                ]);
            }
            
            // Her ay için 8-15 gider işlemi
            $expenseCount = rand(8, 15);
            for ($i = 0; $i < $expenseCount; $i++) {
                $transactionDate = clone $currentDate;
                $transactionDate->addDays(rand(0, 30));
                
                if ($transactionDate > $endDate) {
                    continue;
                }
                
                // Rastgele hesap seç (giderler için kredi kartı da dahil)
                $accounts = [$bankAccount, $creditCard, $cashAccount];
                $account = $accounts[array_rand($accounts)];
                
                // Rastgele kategori seç
                $category = $expenseCategories->random();
                
                // Gider miktarı belirle (kategoriye göre)
                $amount = 0;
                if (strpos(strtolower($category->name), 'kira') !== false || 
                    strpos(strtolower($category->name), 'maaş') !== false) {
                    $amount = rand(3000, 8000) + (rand(0, 99) / 100);
                } else {
                    $amount = rand(50, 1500) + (rand(0, 99) / 100);
                }
                
                // İşlemi oluştur
                $paymentMethod = Transaction::PAYMENT_METHOD_BANK;
                
                if ($account->type === Account::TYPE_CASH) {
                    $paymentMethod = Transaction::PAYMENT_METHOD_CASH;
                } elseif ($account->type === Account::TYPE_BANK_ACCOUNT) {
                    $paymentMethod = Transaction::PAYMENT_METHOD_BANK;
                } elseif ($account->type === Account::TYPE_CREDIT_CARD) {
                    $paymentMethod = Transaction::PAYMENT_METHOD_CREDIT_CARD;
                }
                
                Transaction::create([
                    'user_id' => $user->id,
                    'source_account_id' => $account->id,
                    'category_id' => $category->id,
                    'type' => Transaction::TYPE_EXPENSE,
                    'amount' => $amount,
                    'currency' => 'TRY',
                    'exchange_rate' => 1,
                    'try_equivalent' => $amount,
                    'date' => $transactionDate,
                    'description' => $category->name . ' gideri',
                    'payment_method' => $paymentMethod,
                    'status' => 'completed',
                ]);
            }
            
            // Her 3 ayda bir kripto işlemi (son 1 yıl için)
            if ($currentDate >= Carbon::now()->subYear() && $currentDate->month % 3 === 0) {
                $transactionDate = clone $currentDate;
                $transactionDate->addDays(rand(0, 30));
                
                if ($transactionDate <= $endDate) {
                    // Kripto alımı veya satımı
                    $isBuy = rand(0, 1) === 0;
                    $usdAmount = rand(100, 1000); // 100 - 1000 USD
                    $tryAmount = $usdAmount * 32; // Basit bir USD/TRY kuru
                    
                    // Kategorileri bul
                    $incomeCategory = $incomeCategories->where('name', 'Yatırım Gelirleri')->first();
                    $expenseCategory = $expenseCategories->where('name', 'Diğer Giderler')->first();
                    
                    Transaction::create([
                        'user_id' => $user->id,
                        'source_account_id' => $cryptoWallet->id,
                        'destination_account_id' => $bankAccount->id, // Banka hesabı eklendi
                        'category_id' => $isBuy ? $expenseCategory->id : $incomeCategory->id,
                        'type' => $isBuy ? Transaction::TYPE_EXPENSE : Transaction::TYPE_INCOME,
                        'amount' => $usdAmount,
                        'currency' => 'USD',
                        'exchange_rate' => 32,
                        'try_equivalent' => $tryAmount,
                        'date' => $transactionDate,
                        'description' => $isBuy ? 'USDT (Tether) alımı' : 'USDT (Tether) satışı',
                        'payment_method' => Transaction::PAYMENT_METHOD_CRYPTO, // Kripto ödeme yöntemi
                        'status' => 'completed',
                    ]);
                    
                    // Alım ise banka hesabından para çıkışı, satış ise banka hesabına para girişi
                    Transaction::create([
                        'user_id' => $user->id,
                        'source_account_id' => $bankAccount->id,
                        'destination_account_id' => $cryptoWallet->id, // Kripto cüzdanı eklendi
                        'category_id' => $isBuy ? $expenseCategory->id : $incomeCategory->id,
                        'type' => $isBuy ? Transaction::TYPE_EXPENSE : Transaction::TYPE_INCOME,
                        'amount' => $tryAmount,
                        'currency' => 'TRY',
                        'exchange_rate' => 1,
                        'try_equivalent' => $tryAmount,
                        'date' => $transactionDate,
                        'description' => $isBuy ? 'USDT (Tether) alımı için ödeme' : 'USDT (Tether) satışından gelir',
                        'payment_method' => Transaction::PAYMENT_METHOD_BANK,
                        'status' => 'completed',
                    ]);
                }
            }
            
            // Bir sonraki aya geç
            $currentDate->addMonth();
        }
    }
}
