<x-table.table-layout
    pageTitle="Kredi Kartları"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Kredi Kartları']
    ]"
>
    <livewire:account.widgets.credit-card-stats-widget />

    <div class="mt-6">
        <!-- Tab Headers -->
        <div class="flex space-x-4 border-b border-gray-200">
            <button
                wire:click="$set('activeTab', 'Kredi Kartları')"
                class="relative py-3 px-4 text-sm font-semibold rounded-t-lg transition-all duration-200"
                :class="$activeTab === 'Kredi Kartları' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
            >
                Kredi Kartları
                @if($activeTab === 'Kredi Kartları')
                    <span class="absolute bottom-0 left-0 w-full h-1 bg-indigo-600"></span>
                @endif
            </button>
            <button
                wire:click="$set('activeTab', 'İşlemler')"
                class="relative py-3 px-4 text-sm font-semibold rounded-t-lg transition-all duration-200"
                :class="$activeTab === 'İşlemler' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                :disabled="!$selectedCard"
            >
                İşlemler {{ $selectedCard ? ' - ' . $selectedCard->name : '' }}
                @if($activeTab === 'İşlemler')
                    <span class="absolute bottom-0 left-0 w-full h-1 bg-indigo-600"></span>
                @endif
            </button>
        </div>

        <!-- Tab Content -->
        <div class="mt-4">
            @if($activeTab === 'Kredi Kartları')
                <!-- Credit Cards tab content -->
                {{ $this->table }}
            @elseif($activeTab === 'İşlemler')
                @if($selectedCard)
                    <!-- Transactions tab content -->
                    <livewire:account.transactions-table :accountId="$selectedCard->id" />
                @else
                    <!-- More descriptive message and redirect button -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-center">
                        <p class="text-gray-500 mb-4">
                            Lütfen bir kredi kartı seçin. Kredi Kartları sekmesine dönerek bir kart seçebilirsiniz.
                        </p>
                        <button
                            wire:click="$set('activeTab', 'Kredi Kartları')"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition-all duration-200"
                        >
                            Kredi Kartları Sekmesine Dön
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-table.table-layout>