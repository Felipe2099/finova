<x-table.table-layout
    pageTitle="{{ $customer->name }} ({{ $customer->type === 'corporate' ? 'Kurumsal' : 'Bireysel' }})"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'wire' => true],
        ['label' => 'Müşteriler', 'url' => route('admin.customers.index'), 'wire' => true],
        ['label' => $customer->name]
    ]"
>
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Contact Information --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-base font-semibold mb-4">İletişim Bilgileri</h3>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">E-posta</dt>
                        <dd class="mt-1 text-sm">{{ $customer->email ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Telefon</dt>
                        <dd class="mt-1 text-sm">{{ $customer->phone ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Adres</dt>
                        <dd class="mt-1 text-sm">
                            {{ $customer->address ?? '-' }}
                            @if($customer->city || $customer->district)
                                <br>
                                {{ $customer->district }} / {{ $customer->city }}
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Tax Information --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-base font-semibold mb-4">Vergi Bilgileri</h3>
                <dl class="space-y-4">
                    @if($customer->type === 'corporate')
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Vergi No</dt>
                            <dd class="mt-1 text-sm">{{ $customer->tax_number ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Vergi Dairesi</dt>
                            <dd class="mt-1 text-sm">{{ $customer->tax_office ?? '-' }}</dd>
                        </div>
                    @else
                        <div>
                            <dt class="text-sm font-medium text-gray-500">TC Kimlik No</dt>
                            <dd class="mt-1 text-sm">{{ $customer->tax_number ?? '-' }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Other Information --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-base font-semibold mb-4">Diğer Bilgiler</h3>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Müşteri Grubu</dt>
                        <dd class="mt-1 text-sm">{{ $customer->group?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Durum</dt>
                        <dd class="mt-1">
                            <span @class([
                                'px-2 py-1 text-xs font-medium rounded-full',
                                'bg-green-100 text-green-800' => $customer->status,
                                'bg-red-100 text-red-800' => !$customer->status,
                            ])>
                                {{ $customer->status ? 'Aktif' : 'Pasif' }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="bg-white rounded-lg shadow-sm" x-data="{ activeTab: 'notes' }">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                    <button @click="activeTab = 'notes'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'notes',
                               'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'notes'}"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                        <x-heroicon-m-clipboard-document-list class="w-5 h-5" />
                        Notlar & Aktiviteler
                    </button>
                    <button @click="activeTab = 'financial'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'financial',
                               'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'financial'}"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                        <x-heroicon-m-currency-dollar class="w-5 h-5" />
                        Finansal İşlemler
                    </button>
                    @can('customers.agreements')
                    <button @click="activeTab = 'agreements'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'agreements',
                               'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'agreements'}"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                        <x-heroicon-m-document-text class="w-5 h-5" />
                        Anlaşmalar
                    </button>
                    @endcan
                    @can('customers.credentials')
                    <button @click="activeTab = 'credentials'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'credentials',
                               'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'credentials'}"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                        <x-heroicon-m-key class="w-5 h-5" />
                        Hassas Bilgiler
                    </button>
                    @endcan
                </nav>
            </div>

            <div class="p-6">
                <div x-show="activeTab === 'notes'" x-cloak>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">Notlar & Aktiviteler</h3>
                        <x-filament::button wire:click="addNote">
                            Not Ekle
                        </x-filament::button>
                    </div>

                    {{-- Modal Note --}}
                    <div x-show="$wire.showNoteModal" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 z-50 overflow-y-auto" 
                         x-cloak>
                        <div class="flex min-h-screen items-center justify-center p-4">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                            <div class="relative w-full transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:max-w-lg sm:p-6">
                                <div class="absolute right-0 top-0 pr-4 pt-4">
                                    <button wire:click="$set('showNoteModal', false)" class="text-gray-400 hover:text-gray-500">
                                        <x-heroicon-m-x-mark class="h-6 w-6" />
                                    </button>
                                </div>

                                <div class="sm:flex sm:items-start">
                                    <div class="mt-3 w-full  sm:mt-0 sm:text-left">
                                        <h3 class="text-lg font-semibold leading-6 text-gray-900 mb-4">
                                            {{ $editingNote ? 'Not Düzenle' : 'Not Ekle' }}
                                        </h3>

                                        <div class="mt-2 space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Aktivite Türü</label>
                                                <select wire:model.live="data.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                                    <option value="">Seçiniz</option>
                                                    <option value="note">Not</option>
                                                    <option value="call">Telefon</option>
                                                    <option value="meeting">Toplantı</option>
                                                    <option value="email">E-posta</option>
                                                    <option value="other">Diğer</option>
                                                </select>
                                                @error('data.type') 
                                                    <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Tarih</label>
                                                <input type="datetime-local" wire:model.live="data.activity_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                                @error('data.activity_date') 
                                                    <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Detay</label>
                                                <textarea wire:model.live="data.content" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                                                @error('data.content') 
                                                    <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Atanan Kullanıcı</label>
                                                <select wire:model.live="data.assigned_user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                                    <option value="">Seçiniz</option>
                                                    @foreach($users as $user)
                                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('data.assigned_user_id') 
                                                    <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                                    <x-filament::button wire:click="saveNote">
                                        Kaydet
                                    </x-filament::button>
                                    <x-filament::button color="gray" wire:click="$set('showNoteModal', false)">
                                        İptal
                                    </x-filament::button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Notes List --}}
                    <div class="space-y-4">
                        @forelse($customer->notes as $note)
                            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span @class([
                                                'px-2 py-1 text-xs font-medium rounded-full',
                                                'bg-blue-100 text-blue-800' => $note->type === 'note',
                                                'bg-green-100 text-green-800' => $note->type === 'call',
                                                'bg-purple-100 text-purple-800' => $note->type === 'meeting',
                                                'bg-yellow-100 text-yellow-800' => $note->type === 'email',
                                                'bg-gray-100 text-gray-800' => $note->type === 'other',
                                            ])>
                                                {{ match($note->type) {
                                                    'note' => 'Not',
                                                    'call' => 'Telefon',
                                                    'meeting' => 'Toplantı',
                                                    'email' => 'E-posta',
                                                    'other' => 'Diğer',
                                                } }}
                                            </span>
                                            <span class="text-sm text-gray-500">
                                                {{ $note->activity_date?->format('d.m.Y H:i') }}
                                            </span>
                                            <span class="text-sm text-gray-500">
                                                {{ $note->user->name }}
                                            </span>
                                            @if($note->assigned_user_id)
                                                <span class="text-sm text-gray-500">
                                                    → {{ $note->assignedUser->name }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mt-2 text-sm text-gray-700">{{ $note->content }}</p>
                                    </div>
                                    <div class="ml-auto">
                                        <x-filament::button size="sm" wire:click="editNote({{ $note->id }})">
                                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                                        </x-filament::button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-gray-500 py-8">
                                Henüz not eklenmemiş
                            </div>
                        @endforelse
                    </div>
                </div>

                <div x-show="activeTab === 'financial'" x-cloak>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">Finansal İşlemler</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tarih
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Kategori
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tutar
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ödeme Yöntemi
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Açıklama
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($customer->transactions()->latest('date')->get() as $transaction)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $transaction->date->format('d.m.Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $transaction->category->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span @class([
                                                'font-medium',
                                                'text-green-600' => $transaction->type === 'income',
                                                'text-red-600' => $transaction->type === 'expense',
                                            ])>
                                                {{ match($transaction->currency) {
                                                    'TRY' => '₺',
                                                    'USD' => '$',
                                                    'EUR' => '€',
                                                    'GBP' => '£',
                                                    default => '₺',
                                                } }}{{ number_format($transaction->amount, 2, ',', '.') }}
                                            </span>
                                            @if($transaction->currency !== 'TRY')
                                                <span class="text-xs text-gray-500 block">
                                                    ₺{{ number_format($transaction->try_equivalent, 2, ',', '.') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ match($transaction->payment_method) {
                                                'cash' => 'Nakit',
                                                'bank_transfer' => 'Havale/EFT',
                                                'credit_card' => 'Kredi Kartı',
                                                'paypal' => 'PayPal',
                                                default => '-'
                                            } }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ $transaction->description ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                            Henüz finansal işlem bulunmuyor
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Agreements Tab --}}
                <div x-show="activeTab === 'agreements'" x-cloak>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">Anlaşmalar</h3>
                        <x-filament::button wire:click="addAgreement">
                            Yeni Anlaşma
                        </x-filament::button>
                    </div>

                    {{-- Agreements List --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Anlaşma
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tutar
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Başlangıç
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Sonraki Ödeme
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Durum
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        İşlemler
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($agreements as $agreement)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $agreement->name }}
                                            @if($agreement->description)
                                                <p class="text-xs text-gray-500">{{ $agreement->description }}</p>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="font-medium text-green-600">
                                                ₺{{ number_format($agreement->amount, 2, ',', '.') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $agreement->start_date->format('d.m.Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $agreement->next_payment_date->format('d.m.Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                @if($agreement->status === 'active') bg-green-100 text-green-800
                                                @elseif($agreement->status === 'completed') bg-blue-100 text-blue-800
                                                @else bg-red-100 text-red-800 @endif">
                                                {{ match($agreement->status) {
                                                    'active' => 'Aktif',
                                                    'completed' => 'Tamamlandı',
                                                    'cancelled' => 'İptal',
                                                    default => $agreement->status
                                                } }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex space-x-2">
                                                <x-filament::button size="sm" wire:click="editAgreement({{ $agreement->id }})">
                                                    <x-heroicon-m-pencil-square class="w-4 h-4 mr-1" />
                                                </x-filament::button>
                                                <x-filament::button size="sm" color="danger" wire:click="deleteAgreement({{ $agreement->id }})">
                                                    <x-heroicon-m-trash class="w-4 h-4 mr-1" />
                                                </x-filament::button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            Henüz anlaşma bulunmuyor
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Agreement Add/Edit Modal --}}
                    <div x-show="$wire.showAgreementModal" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 z-50 overflow-y-auto" 
                         x-cloak>
                        <div class="flex min-h-screen items-center justify-center p-4">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                            <div class="relative w-full transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:max-w-lg sm:p-6">
                                <div class="absolute right-0 top-0 pr-4 pt-4">
                                    <button wire:click="$set('showAgreementModal', false)" class="text-gray-400 hover:text-gray-500">
                                        <x-heroicon-m-x-mark class="h-6 w-6" />
                                    </button>
                                </div>

                                <div class="sm:flex sm:items-start">
                                    <div class="mt-3 w-full sm:mt-0 sm:text-left">
                                        <h3 class="text-lg font-semibold leading-6 text-gray-900 mb-4">
                                            {{ $editingAgreement ? 'Anlaşma Düzenle' : 'Yeni Anlaşma' }}
                                        </h3>

                                        <div class="mt-2 space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Anlaşma Adı</label>
                                                <input type="text" wire:model.live="agreementData.name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                                @error('agreementData.name') 
                                                    <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Açıklama</label>
                                                <textarea wire:model.live="agreementData.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                                                @error('agreementData.description') 
                                                    <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Tutar</label>
                                                <input type="number" step="0.01" wire:model.live="agreementData.amount" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                                @error('agreementData.amount') 
                                                    <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Başlangıç Tarihi</label>
                                                <input type="date" wire:model.live="agreementData.start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                                @error('agreementData.start_date') 
                                                    <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Sonraki Ödeme Tarihi</label>
                                                <input type="date" wire:model.live="agreementData.next_payment_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                                @error('agreementData.next_payment_date') 
                                                    <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            @if($editingAgreement)
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Durum</label>
                                                    <select wire:model="agreementData.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                                        <option value="active">Aktif</option>
                                                        <option value="completed">Tamamlandı</option>
                                                        <option value="cancelled">İptal</option>
                                                    </select>
                                                    @error('agreementData.status') 
                                                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                                    <x-filament::button wire:click="saveAgreement">
                                        {{ $editingAgreement ? 'Güncelle' : 'Kaydet' }}
                                    </x-filament::button>
                                    <x-filament::button color="gray" wire:click="$set('showAgreementModal', false)">
                                        İptal
                                    </x-filament::button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sensitive Information Tab --}}
                <div x-show="activeTab === 'credentials'" x-cloak>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">Hassas Bilgiler</h3>
                        <x-filament::button wire:click="addCredential">
                            Yeni Bilgi
                        </x-filament::button>
                    </div>

                    {{-- Sensitive Information List --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @forelse($customer->credentials()->latest()->get() as $credential)
                            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-sm font-medium text-gray-900">{{ $credential->name }}</h3>
                                    <div class="flex space-x-2">
                                        <x-filament::button size="sm" wire:click="editCredential({{ $credential->id }})">
                                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                                        </x-filament::button>
                                        <x-filament::button size="sm" color="danger" wire:click="deleteCredential({{ $credential->id }})">
                                            <x-heroicon-m-trash class="w-4 h-4" />
                                        </x-filament::button>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    @foreach($credential->value as $value)
                                        <div class="text-sm text-gray-600">{{ trim($value, '"') }}</div>
                                    @endforeach
                                </div>

                                <div class="mt-2 text-xs text-gray-500 text-right">
                                    {{ $credential->user->name }} tarafından eklendi
                                </div>
                            </div>
                        @empty
                            <div class="md:col-span-2 text-center text-gray-500 py-8">
                                Henüz hassas bilgi bulunmuyor
                            </div>
                        @endforelse
                    </div>

                    {{-- Sensitive Information Add/Edit Modal --}}
                    <div x-show="$wire.showCredentialModal" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 z-50 overflow-y-auto" 
                         x-cloak>
                        <div class="flex min-h-screen items-center justify-center p-4">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                            <div class="relative w-full transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:max-w-lg sm:p-6">
                                <div class="absolute right-0 top-0 pr-4 pt-4">
                                    <button wire:click="$set('showCredentialModal', false)" class="text-gray-400 hover:text-gray-500">
                                        <x-heroicon-m-x-mark class="h-6 w-6" />
                                    </button>
                                </div>

                                <div class="sm:flex sm:items-start">
                                    <div class="mt-3 w-full sm:mt-0 sm:text-left">
                                        <h3 class="text-lg font-semibold leading-6 text-gray-900 mb-4">
                                            Hassas Bilgi Ekle
                                        </h3>

                                        <div class="mt-2 space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Başlık</label>
                                                <input type="text" wire:model.live="credentialData.name" placeholder="Reklam hesabı, Sunucu, Domain..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                                @error('credentialData.name') 
                                                    <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Bilgiler</label>
                                                <div class="mt-1 space-y-2">
                                                    @foreach($credentialData['value'] ?? [] as $index => $value)
                                                        <div class="flex space-x-2">
                                                            <input type="text" wire:model.live="credentialData.value.{{ $index }}" placeholder="Bilgi" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                                            <button type="button" wire:click="removeCredentialValue({{ $index }})" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                                <x-heroicon-m-x-mark class="h-5 w-5" />
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                    <button type="button" wire:click="addCredentialValue" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                                        <x-heroicon-m-plus class="h-5 w-5 mr-2" />
                                                        Yeni Bilgi Ekle
                                                    </button>
                                                </div>
                                                @error('credentialData.value') 
                                                    <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                                    <x-filament::button wire:click="saveCredential">
                                        Kaydet
                                    </x-filament::button>
                                    <x-filament::button color="gray" wire:click="$set('showCredentialModal', false)">
                                        İptal
                                    </x-filament::button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-table.table-layout> 