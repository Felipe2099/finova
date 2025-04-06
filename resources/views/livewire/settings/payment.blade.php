<x-form.form-layout
    :content="null"
    pageTitle="Site Settings"
    backLabel="Back to Settings"
    :backgroundCard="false"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Settings', 'url' => route('admin.settings.index'), 'wire' => true],
        ['label' => 'Payment']
    ]"

>
    {{ $this->form }}
</x-form.form-layout> 