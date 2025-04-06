<aside x-data="{ 
    activeMenu: @js(str_contains(request()->route()->getName(), 'accounts') ? 'accounts' : 
               (str_contains(request()->route()->getName(), 'transactions') ? 'financial_transactions' : 
               (str_contains(request()->route()->getName(), 'admin.analysis') ? 'analysis_tracking' :
               (str_contains(request()->route()->getName(), 'admin.customers') ? 'customer_management' : null)))),
    menuGroups: {
        'financial_transactions': [
            'admin.transactions.index',
            'admin.transactions.create',
            'admin.transactions.edit',
            'admin.subscriptions.index',
            'admin.deposits.index',
            'admin.credit-cards.index',
            'admin.credit-cards.transactions',
            'admin.debts.index',
            'admin.debts.payments',
            'admin.suppliers.index',
            'admin.loans.index',
            'admin.loans.payments',
            'admin.loans.details'
        ],
        'customer_management': [
            'admin.customers.index',
            'admin.customers.groups',
            'admin.customers.potential',
            'admin.customers.show'
        ],
        'project_management': [
            'admin.projects.index',
            'admin.projects.active',
            'admin.projects.completed'
        ],
        'proposal_management': [
            'admin.proposals.list',
            'admin.proposals.templates'
        ],
        'categories': [
            'admin.categories.index',
        ],
        'analysis_tracking': [
            'admin.analysis.cash-flow'
        ],
        'planning': [
            'admin.planning.savings',
            'admin.planning.investments'
        ],
        'reports': ['admin.reports.financial', 'admin.reports.customer', 'admin.reports.project', 'admin.reports.tax'],
        'documents': ['admin.documents.contracts', 'admin.documents.proposals', 'admin.documents.templates'],
        'system_settings': ['admin.settings', 'admin.roles.index', 'admin.users', 'admin.logs.index'],
        'accounts': [
            'admin.accounts.bank',
            'admin.accounts.credit-cards',
            'admin.accounts.crypto',
            'admin.accounts.virtual-pos'
        ]
    },

    init() {
        this.setActiveMenu();
        
        // Livewire navigasyonunu dinle
        Livewire.on('navigated', () => this.setActiveMenu());
    },

    setActiveMenu() {
        const currentRoute = '{{ request()->route()->getName() }}';
        
        // Her menü grubu için kontrol et
        Object.entries(this.menuGroups).forEach(([group, routes]) => {
            if (routes.some(route => currentRoute.startsWith(route))) {
                this.activeMenu = group;
            }
        });
    },

    isActive(routeName) {
        const currentRoute = '{{ request()->route()->getName() }}';
        
        // Müşteri detay sayfası için özel kontrol
        if (routeName === 'admin.customers.index') {
            return currentRoute === 'admin.customers.index' || currentRoute === 'admin.customers.show';
        }
        
        // Kredi kartları için özel kontrol
        if (routeName === 'admin.credit-cards') {
            return currentRoute.startsWith('admin.credit-cards.');
        }
        
        // Proje ve board sayfaları için özel kontrol
        if (routeName === 'admin.projects.index') {
            return currentRoute.startsWith('admin.projects.');
        }
        
        // Borç/Alacak için özel kontrol
        if (routeName === 'admin.debts') {
            return currentRoute.startsWith('admin.debts.');
        }
        
        // Diğer sayfalar için normal kontrol
        return currentRoute.startsWith(routeName);
    },

    // Kullanıcının izinlerine göre menüleri göster/gizle
    canViewMenu(menu) {
        const permissions = {
            'financial_transactions': ['view_finances', 'manage_finances'],
            'customer_management': ['view_customers', 'manage_customers'],
            'project_management': ['view_projects', 'manage_projects'],
            'invoice_management': ['view_invoices', 'manage_invoices'],
            'analysis_tracking': ['view_reports', 'view_analysis'],
            'user_management': ['manage_staff', 'manage_roles']
        };
        
        // Super Admin ve Owner her şeyi görebilir
        if (@json(auth()->user()->hasRole(['super_admin', 'owner']))) {
            return true;
        }
        
    }
}" 
id="sidebar" 
class="fixed left-0 top-0 z-40 h-screen pt-16 w-64 bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700 transition-transform duration-300 -translate-x-full lg:translate-x-0" 
aria-label="Sidebar">
    <!-- Logo Container -->
    <div class="absolute top-0 left-0 right-0 h-16 border-b border-gray-200 dark:border-gray-700">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center h-full px-3">
            <img src="https://flowbite.s3.amazonaws.com/logo.svg" class="h-8 mr-3" alt="Logo" />
            <span class="self-center text-xl font-semibold whitespace-nowrap dark:text-white">{{ config('app.name') }}</span>
        </a>
    </div>

    <div class="h-full px-3 pb-4 overflow-y-auto my-4">
        <ul class="space-y-2">
            <li>
                <a href="{{ route('admin.dashboard') }}" 
                   wire:navigate 
                   class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                   :class="{'bg-gray-100': isActive('admin.dashboard')}">
                    <svg class="w-6 h-6 text-gray-500 transition duration-75" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path>
                        <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path>
                    </svg>
                    <span class="ml-3">Dashboard</span>
                </a>
            </li>
            <li>
                <button @click="activeMenu = activeMenu === 'customer_management' ? null : 'customer_management'" 
                        type="button" 
                        class="flex items-center p-2 w-full text-base font-normal text-gray-900 rounded-lg">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    <span class="flex-1 ml-3 text-left">Müşteriler</span>
                    <svg :class="{'rotate-180': activeMenu === 'customer_management'}" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
                <ul x-show="activeMenu === 'customer_management'" x-collapse class="py-2 space-y-2">
                    <li>
                        <a href="{{ route('admin.customers.index') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.customers.index')}">
                            Müşteri Listesi
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.customers.potential') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.customers.potential')}">
                            Potansiyel Müşteriler
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.customers.groups') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.customers.groups')}">
                            Müşteri Grupları
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="{{ route('admin.projects.index') }}" 
                   wire:navigate 
                   class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                   :class="{'bg-gray-100': isActive('admin.projects.index')}">
                   <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1 1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1 1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1 1z" clip-rule="evenodd"/>
                    </svg>
                    <span class="ml-3">Projeler</span>
                </a>
            </li>
            <!--
            <li>
                <a href="{{ route('admin.proposals.templates') }}" 
                   wire:navigate 
                   class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                   :class="{'bg-gray-100': isActive('admin.proposals.templates')}">
                   <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"/>
                        <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"/>
                    </svg>
                    <span class="ml-3">Teklifler</span>
                </a>
            </li>
            -->

            <!--
            <li>
                <button @click="activeMenu = activeMenu === 'invoice_management' ? null : 'invoice_management'" 
                        type="button" 
                        class="flex items-center p-2 w-full text-base font-normal text-gray-900 rounded-lg">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                    </svg>
                    <span class="flex-1 ml-3 text-left">Faturalar</span>
                    <svg :class="{'rotate-180': activeMenu === 'invoice_management'}" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
                <ul x-show="activeMenu === 'invoice_management'" x-collapse class="py-2 space-y-2">
                    <li>
                        <a href="#" class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100">
                            Fatura Oluştur
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100">
                            Fatura Listesi
                        </a>
                    </li>
                </ul>
            </li>
            -->
            <li>
                <button @click="activeMenu = activeMenu === 'accounts' ? null : 'accounts'" 
                        type="button" 
                        class="flex items-center p-2 w-full text-base font-normal text-gray-900 rounded-lg">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="flex-1 ml-3 text-left">Hesaplar</span>
                    <svg :class="{'rotate-180': activeMenu === 'accounts'}" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
                <ul x-show="activeMenu === 'accounts'" x-collapse class="py-2 space-y-2">
                    <li>
                        <a href="{{ route('admin.accounts.bank') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': '{{ request()->routeIs('admin.accounts.bank') }}' === '1'}">
                           Banka Hesapları
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.accounts.credit-cards') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': '{{ request()->routeIs('admin.accounts.credit-cards') }}' === '1'}">
                           Kredi Kartları
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.accounts.crypto') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': '{{ request()->routeIs('admin.accounts.crypto') }}' === '1'}">
                           Kripto Cüzdanları
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.accounts.virtual-pos') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': '{{ request()->routeIs('admin.accounts.virtual-pos') }}' === '1'}">
                           Sanal POS
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <button @click="activeMenu = activeMenu === 'financial_transactions' ? null : 'financial_transactions'" 
                        type="button" 
                        class="flex items-center p-2 w-full text-base font-normal text-gray-900 rounded-lg">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path>
                        <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="flex-1 ml-3 text-left">Finans Yönetimi</span>
                    <svg :class="{'rotate-180': activeMenu === 'financial_transactions'}" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
                <ul x-show="activeMenu === 'financial_transactions'" 
                    x-collapse 
                    class="py-2 space-y-2">
                    <!-- Tüm İşlemler -->
                    <li>
                        <a href="{{ route('admin.transactions.index') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.transactions')}">
                           Tüm İşlemler
                        </a>
                    </li>
  
                    <!-- Borç-Alacak -->
                    <li>
                        <a href="{{ route('admin.debts.index') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': '{{ request()->routeIs('admin.debts.index') }}' === '1'}">
                           Borç-Alacak
                        </a>
                    </li>
                    <!-- Krediler -->
                    <li>
                        <a href="{{ route('admin.loans.index') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': '{{ request()->routeIs('admin.loans.index') }}' === '1'}">
                           Krediler
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <button @click="activeMenu = activeMenu === 'analysis_tracking' ? null : 'analysis_tracking'" 
                        type="button" 
                        class="flex items-center p-2 w-full text-base font-normal text-gray-900 rounded-lg">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                    </svg>
                    <span class="flex-1 ml-3 text-left">Analizler</span>
                    <svg :class="{'rotate-180': activeMenu === 'analysis_tracking'}" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
                <ul x-show="activeMenu === 'analysis_tracking'" x-collapse class="py-2 space-y-2">
                    <li>
                        <a href="{{ route('admin.analysis.cash-flow') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.analysis.cash-flow')}">
                            Nakit Akışı
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.analysis.categories') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.analysis.categories')}">
                            Kategori Analizi
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <button @click="activeMenu = activeMenu === 'planning' ? null : 'planning'" 
                        type="button" 
                        class="flex items-center p-2 w-full text-base font-normal text-gray-900 rounded-lg">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                    </svg>
                    <span class="flex-1 ml-3 text-left">Planlama</span>
                    <svg :class="{'rotate-180': activeMenu === 'planning'}" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
                <ul x-show="activeMenu === 'planning'" x-collapse class="py-2 space-y-2">
                    <li>
                        <a href="{{ route('admin.planning.savings') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.planning.savings')}">
                           Tasarruf Planları
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.planning.investments') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.planning.investments')}">
                           Yatırım Planları
                        </a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="{{ route('admin.categories.index') }}" 
                   wire:navigate 
                   class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                   :class="{'bg-gray-100': isActive('admin.categories.index')}">
                   <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    <span class="ml-3">Kategoriler</span>
                </a>
            </li>
     
            <li>
                <button @click="activeMenu = activeMenu === 'system_settings' ? null : 'system_settings'" 
                        type="button" 
                        class="flex items-center p-2 w-full text-base font-normal text-gray-900 rounded-lg">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="flex-1 ml-3 text-left">Sistem</span>
                    <svg :class="{'rotate-180': activeMenu === 'system_settings'}" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
                <ul x-show="activeMenu === 'system_settings'" x-collapse class="py-2 space-y-2">
                    <li>
                        <a href="{{ route('admin.settings.index') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.settings')}">
                           Ayarlar
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.roles.index') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.roles')}">
                           Roller & İzinler
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.users.index') }}" 
                           wire:navigate 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100"
                           :class="{'bg-gray-100': isActive('admin.users')}">
                           Kullanıcılar
                        </a>
                    </li>
                    <li>
                        <a href="#" 
                           class="flex items-center p-2 pl-11 w-full text-base font-normal rounded-lg hover:bg-gray-100"
                           style="color: #3E82F8">
                           Yardım
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</aside>
