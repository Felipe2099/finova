<x-table.table-layout
    pageTitle="Müşteri Grupları"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Müşteri Grupları', 'url' => route('admin.customers.index'), 'wire' => true],
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 