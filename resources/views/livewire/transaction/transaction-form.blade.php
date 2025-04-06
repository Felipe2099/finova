<x-form.form-layout
    :content="$transaction"
    pageTitle="{{ $transaction ? 'İşlem Düzenle' : 'Yeni İşlem' }}"
    backRoute="{{ route('admin.transactions.index') }}"
    backLabel="İşlemlere Dön"
    :backgroundCard="false"
    :isTransaction="true"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'İşlemler', 'url' => route('admin.transactions.index'), 'wire' => true],
        ['label' => $transaction ? 'İşlem Düzenle' : 'Yeni İşlem', 'url' => '', 'wire' => true],
    ]"
>
    {{ $this->form }}
</x-form.form-layout> 