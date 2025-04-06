<x-table.table-layout
    pageTitle="Sanal POS"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Sanal POS']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 