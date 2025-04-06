<x-form.form-layout
    :content="$record"
    pageTitle="{{ $isEdit ? 'Teklif Düzenle' : 'Teklif Oluştur' }}"
    backRoute="{{ route('admin.proposals.templates') }}"
    backLabel="Tekliflere Dön"
    :backgroundCard="false"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Teklifler', 'url' => route('admin.proposals.templates'), 'wire' => true],
        ['label' => $isEdit ? 'Düzenle' : 'Oluştur']
    ]"
>
{{ $this->form }}

</x-form.form-layout>
