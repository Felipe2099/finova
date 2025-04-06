<x-form.form-layout
    :pageTitle="$isEdit ? 'Rol Düzenle: ' . $role->name : 'Yeni Rol Oluştur'"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard'), 'wire' => true], // Adjust if needed
        ['label' => 'Roller', 'url' => route('roles.index'), 'wire' => true],
        ['label' => $isEdit ? 'Düzenle' : 'Oluştur']
    ]"
    backRoute="{{ route('roles.index') }}"
    backLabel="Rollere Dön"
>
    {{ $this->form }}

</x-form.form-layout> 