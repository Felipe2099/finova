<x-table.table-layout
        pageTitle="Teklifler"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
            ['label' => 'Teklifler'],
        ]"
    >
        {{ $this->table }}
</x-table.table-layout>