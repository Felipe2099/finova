<x-table.table-layout
    pageTitle="Mevduatlar"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Mevduatlar']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 