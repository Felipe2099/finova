<x-table.table-layout
    pageTitle="Krediler"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Krediler']
    ]"
>
    {{ $this->table }}
</x-table.table-layout>