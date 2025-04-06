<x-table.table-layout
    pageTitle="{{ $account->name }} - İşlem Geçmişi"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Tüm Hesaplar']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 