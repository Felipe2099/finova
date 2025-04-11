<x-form.form-layout
    :content="$role"
    :pageTitle="$isEdit ? 'Rol Düzenle: ' . $role->name : 'Yeni Rol Oluştur'"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Roller', 'url' => route('admin.roles.index'), 'wire' => true],
        ['label' => $isEdit ? 'Düzenle' : 'Oluştur']
    ]"
    backRoute="{{ route('admin.roles.index') }}"
    backLabel="Rollere Dön"
>
    {{ $this->form }}
</x-form.form-layout>