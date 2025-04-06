<x-table.table-layout
    pageTitle="Tedarikçiler"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Tedarikçiler']
    ]"
>
    {{ $this->table }}
</x-table.table-layout>