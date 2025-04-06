<x-table.table-layout
    :pageTitle="$creditCard->card_name . ' - İşlemler'"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Kredi Kartları', 'url' => route('admin.credit-cards.index'), 'wire' => true],
        ['label' => $creditCard->card_name]
    ]"
>
    <div>
        {{ $this->table }}
    </div>
</x-table.table-layout>
