<x-table.table-layout
    pageTitle="Gelir & Gider Kategorileri"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Gelir & Gider Kategorileri']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 