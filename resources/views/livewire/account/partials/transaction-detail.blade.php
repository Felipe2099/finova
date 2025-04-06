<div class="p-4 space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <div class="text-sm font-medium text-gray-500">İşlem Tarihi</div>
            <div class="mt-1">{{ $transaction->created_at->format('d.m.Y H:i') }}</div>
        </div>
        
        <div>
            <div class="text-sm font-medium text-gray-500">İşlem Tipi</div>
            <div class="mt-1">
                @php
                    $color = match($transaction->transaction_type) {
                        'purchase' => 'text-red-600',
                        'payment' => 'text-green-600',
                        'refund' => 'text-blue-600',
                    };
                    
                    $type = match($transaction->transaction_type) {
                        'purchase' => 'Kredi Kartı Harcaması',
                        'payment' => 'Kredi Kartı Borç Ödemesi',
                        'refund' => 'Ödeme İadesi',
                    };
                @endphp
                <span class="{{ $color }}">{{ $type }}</span>
            </div>
        </div>

        <div>
            <div class="text-sm font-medium text-gray-500">Tutar</div>
            <div class="mt-1 {{ $transaction->is_income ? 'text-green-600' : 'text-red-600' }}">
                {{ $transaction->is_income ? '+' : '-' }} {{ number_format($transaction->amount, 2) }} ₺
            </div>
        </div>

        @if($transaction->installments)
            <div>
                <div class="text-sm font-medium text-gray-500">Taksit</div>
                <div class="mt-1">{{ $transaction->installments }} Taksit</div>
            </div>
        @endif
    </div>

    <div>
        <div class="text-sm font-medium text-gray-500">Açıklama</div>
        <div class="mt-1">{{ $transaction->description }}</div>
    </div>

    @if($transaction->installments)
        <div>
            <div class="text-sm font-medium text-gray-500 mb-2">Taksit Detayı</div>
            <div class="bg-gray-50 p-3 rounded-lg">
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="font-medium">Taksit Tutarı</div>
                        <div class="mt-1">{{ number_format($transaction->amount / $transaction->installments, 2) }} ₺</div>
                    </div>
                    <div>
                        <div class="font-medium">Toplam Tutar</div>
                        <div class="mt-1">{{ number_format($transaction->amount, 2) }} ₺</div>
                    </div>
                    <div>
                        <div class="font-medium">Taksit Sayısı</div>
                        <div class="mt-1">{{ $transaction->installments }} Ay</div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div> 