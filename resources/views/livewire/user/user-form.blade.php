<x-form.form-layout
    :content="$user"
    pageTitle="{{ $isEdit ? 'Kullanıcı Düzenle' : 'Kullanıcı Oluştur' }}"
    backRoute="{{ route('admin.users.index') }}"
    backLabel="Kullanıcılara Dön"
    :backgroundCard="false"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Kullanıcılar', 'url' => route('admin.users.index'), 'wire' => true],
        ['label' => $isEdit ? 'Düzenle' : 'Oluştur']
    ]"
>
    {{-- Formun kendisi --}}
    {{ $this->form }}

    @if ($showRestoreModal && $deletedUser)
        <div
            x-data="{ open: @entangle('showRestoreModal') }"
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                {{-- Arka plan overlay --}}
                <div
                    x-show="open"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                    aria-hidden="true"
                ></div>

                {{-- Modal içeriği dikeyde ortalamak için --}}
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                {{-- Modal paneli --}}
                <div
                    x-show="open"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6"
                >
                    <div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                Silinmiş Kullanıcı Bulundu
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Bu e-posta adresine (<strong>{{ $deletedUser->email }}</strong>) sahip silinmiş bir kullanıcı bulundu.
                                    Bu kullanıcıyı formdaki yeni bilgilerle geri yükleyip güncellemek istiyor musunuz?
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button
                            wire:click="confirmRestore"
                            type="button"
                            class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm"
                        >
                            Evet, Geri Yükle ve Güncelle
                        </button>
                        <button
                            wire:click="cancelRestore"
                            type="button"
                            class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm"
                        >
                            Hayır, Vazgeç
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</x-form.form-layout>