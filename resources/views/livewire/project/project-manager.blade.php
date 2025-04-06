<x-table.table-layout
    pageTitle="Projeler"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Projeler']
    ]"
>
{{ $this->table }}
</x-table.table-layout> 