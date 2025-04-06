<x-table.table-layout
    pageTitle="Kripto Cüzdanları"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Kripto Cüzdanları']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 