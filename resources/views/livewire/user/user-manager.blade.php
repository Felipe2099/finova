<x-table.table-layout 
    pageTitle="Kullanıcı Yönetimi"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true, 'icon' => 'fas fa-home'],
        ['label' => 'Kullanıcılar', 'icon' => 'fas fa-user'],
        ['label' => 'Liste']
    ]"
>
    {{ $this->table }}
</x-table.table-layout> 