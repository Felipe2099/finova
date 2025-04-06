<x-table.table-layout
    pageTitle="Müşteriler"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Müşteriler']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 