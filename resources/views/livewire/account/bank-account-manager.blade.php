<x-table.table-layout
    pageTitle="Banka Hesapları"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Banka Hesapları']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 