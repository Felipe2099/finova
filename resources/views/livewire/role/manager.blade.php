<x-table.table-layout 
    pageTitle="Rol Yönetimi"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true, 'icon' => 'fas fa-home'],
        ['label' => 'Roller', 'icon' => 'fas fa-user-shield'],
        ['label' => 'Liste']
    ]"
>
    {{ $this->table }}
</x-table.table-layout>