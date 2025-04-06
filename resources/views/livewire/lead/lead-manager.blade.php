<x-table.table-layout
    pageTitle="Potansiyel Müşteriler"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Potansiyel Müşteriler']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 