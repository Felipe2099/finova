<x-table.table-layout
    pageTitle="Tasarruf Planları"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Tasarruf Planları']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 