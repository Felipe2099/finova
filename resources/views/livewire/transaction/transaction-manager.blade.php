<x-table.table-layout
    pageTitle="İşlemler"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'İşlemler']
    ]"
>
    <div class="mb-6">
        <livewire:transaction.widgets.transaction-stats-widget />
    </div>

    {{ $this->table }}
</x-table.table-layout> 