<x-table.table-layout 
    pageTitle="Role Management"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true, 'icon' => 'fas fa-home'],
        ['label' => 'Roles', 'icon' => 'fas fa-user-shield'],
        ['label' => 'List']
    ]"
>
    {{ $this->table }}
</x-table.table-layout>