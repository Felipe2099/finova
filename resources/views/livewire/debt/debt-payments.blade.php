<x-table.table-layout
    pageTitle="Borç & Alacak Ödemeleri"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Borç & Alacak Takibi', 'url' => route('admin.debts.index'), 'wire' => true],
        ['label' => 'Ödemeler']
    ]"
>
    <div class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @foreach([
                [
                    'label' => 'Borçlu/Alacaklı',
                    'value' => $debt->type === 'debt_payment' 
                        ? ($debt->customer?->name ?? '-') 
                        : ($debt->supplier?->name ?? '-'),
                    'color' => 'primary',
                ],
                [
                    'label' => 'Toplam Tutar',
                    'value' => $debt->formatted_amount,
                    'color' => 'success',
                ],
                [
                    'label' => 'Kalan Tutar',
                    'value' => $debt->formatted_remaining_amount,
                    'color' => 'warning',
                ],
                [
                    'label' => 'Vade Tarihi',
                    'value' => $debt->due_date?->format('d.m.Y') ?? '-',
                    'color' => 'info',
                ],
            ] as $stat)
                <x-card.stat-card
                    :label="$stat['label']"
                    :value="$stat['value']"
                    :color="$stat['color']"
                />
            @endforeach
        </div>

        @if($debt->currency === 'XAU')
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">Alış Fiyatı:</span>
                        <span class="ml-2 font-medium">₺{{ number_format($debt->buy_price, 2) }}/GR</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Güncel Altın Fiyatı:</span>
                        <span class="ml-2 font-medium">₺{{ number_format($this->getCurrentGoldPrice(), 2) }}/GR</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Fiyat Farkı:</span>
                        <span class="ml-2 font-medium {{ $this->getCurrentGoldPrice() > $debt->buy_price ? 'text-red-600' : 'text-green-600' }}">
                            ₺{{ number_format($this->getCurrentGoldPrice() - $debt->buy_price, 2) }}/GR
                        </span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{ $this->table }}
</x-table.table-layout>