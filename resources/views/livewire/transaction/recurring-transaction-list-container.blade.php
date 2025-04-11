<x-table.table-layout
    pageTitle="Devamlı İşlemler"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'İşlemler']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 
