<x-form.form-layout
    :content="$category"
    pageTitle="{{ $category->exists ? 'Gelir Kategorisi Düzenle' : 'Gelir Kategorisi Ekle' }}"
    backRoute="{{ route('admin.categories') }}"
    backLabel="Gelir Kategorilerine Dön"
    :backgroundCard="false"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Gelir Kategorileri', 'url' => route('admin.categories'), 'wire' => true],
    ]"
>
{{ $this->form }}
</x-form.form-layout> 