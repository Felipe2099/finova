<x-table.table-layout
    pageTitle="Borç & Alacak Takibi"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Borç & Alacak Takibi']
    ]"
>

    {{ $this->table }}
</x-table.table-layout> 