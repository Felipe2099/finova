<x-table.table-layout
    pageTitle="Kredi Kartları"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Kredi Kartları']
    ]"
>

    <livewire:credit-card.widgets.credit-card-stats-widget />
    {{ $this->table }}
</x-table.table-layout> 