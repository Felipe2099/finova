<x-table.table-layout
    pageTitle="Yatırım Planları"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Yatırım Planları']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 