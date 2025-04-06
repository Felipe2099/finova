<x-table.table-layout 
    pageTitle="{{ $user->name }} - Komisyon Geçmişi"
    
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Kullanıcılar', 'url' => route('admin.users.index'), 'wire' => true],
        ['label' => $user->name, 'url' => route('admin.users.edit', $user), 'wire' => true],
        ['label' => 'Komisyon Geçmişi']
    ]"
>
    <div class="space-y-6">
        {{-- Komisyon İstatistikleri --}}
        @livewire('commission.widgets.commission-stats', ['user' => $user])

        {{-- Ödeme Tarihi Uyarısı --}}
        <div class="bg-orange-100 border-l-4 bg-red-600 p-4 mb-4 rounded-md shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-white font-medium">
                        <strong>Önemli Bilgi:</strong> <br>Ödeme tarihi, istatistiklerin doğru hesaplanması için kritik öneme sahiptir. Örneğin, 01.03.2025-31.03.2025 arası gelirler mart ayına yansır. Nisan ayında ödeme yapacaksanız bile, ödeme tarihini mart içinde seçmelisiniz. Eğer 5 nisan gibi bir tarih seçerseniz, nisan ayı içinde kalan ödeme miktarı eksi bakiye olarak gözükebilir.
                    </p>
                </div>
            </div>
        </div>

        {{-- Tablo Seçimi ve İçerik --}}
        <div class="bg-white rounded-lg shadow-sm">
            {{-- Tab Butonları --}}
            <nav class="border-b border-gray-200">
                <div class="flex space-x-8 px-6" aria-label="Tabs">
                    <button 
                        type="button"
                        wire:click="switchTable('commissions')"
                        @class([
                            'py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap',
                            'border-primary-500 text-primary-600' => $activeTable === 'commissions',
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' => $activeTable !== 'commissions',
                        ])
                        role="tab"
                        aria-selected="{{ $activeTable === 'commissions' ? 'true' : 'false' }}"
                    >
                        Komisyon Kazançları
                    </button>
                    <button 
                        type="button"
                        wire:click="switchTable('payouts')"
                        @class([
                            'py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap',
                            'border-primary-500 text-primary-600' => $activeTable === 'payouts',
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' => $activeTable !== 'payouts',
                        ])
                        role="tab"
                        aria-selected="{{ $activeTable === 'payouts' ? 'true' : 'false' }}"
                    >
                        Komisyon Ödemeleri
                    </button>
                </div>
            </nav>

            {{ $this->table }}
        </div>
    </div>
</x-table.table-layout> 