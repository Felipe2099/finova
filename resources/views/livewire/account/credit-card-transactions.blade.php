<x-table.table-layout
    :pageTitle="$account->name . ' - İşlemler'"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Hesaplar', 'url' => route('admin.accounts.index'), 'wire' => true],
        ['label' => 'Kredi Kartları', 'url' => route('admin.accounts.credit-cards'), 'wire' => true],
        ['label' => $account->name]
    ]"
>
    <div>
        {{ $this->table }}
    </div>
</x-table.table-layout> 