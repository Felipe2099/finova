<x-modal wire:model="showTaskModal" maxWidth="2xl">
    <form wire:submit="createTask" class="p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">
            {{ $editingTask ? 'Görevi Düzenle' : 'Yeni Görev' }}
        </h2>

        <div class="space-y-4">
            {{-- Başlık ve Kısa Açıklama yan yana --}}
            <div class="grid grid-cols-1 gap-4">
                <x-form-elements.input 
                    wire:model="taskData.title" 
                    label="Başlık"
                    required
                />

        
            </div>

            {{-- İçerik Editörü --}}
            <x-form-elements.rich-editor
                wire:model="taskData.content"
                label="Detaylı Açıklama"
            />

            {{-- Öncelik ve Bitiş Tarihi yan yana --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-form-elements.select
                    wire:model="taskData.priority"
                    label="Öncelik"
                    :options="[
                        'low' => 'Düşük',
                        'medium' => 'Orta',
                        'high' => 'Yüksek'
                    ]"
                />

                <x-form-elements.date
                    wire:model="taskData.due_date"
                    label="Bitiş Tarihi"
                />
            </div>

            {{-- Atanan Kişi --}}
            <x-form-elements.select
                wire:model="taskData.assigned_to"
                label="Atanan Kişi"
                :options="$users->pluck('name', 'id')->prepend('Seçiniz...', '')"
            />

            {{-- Checklist --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kontrol Listesi</label>
                <div class="mt-2 space-y-2">
                    @foreach($taskData['checklist'] ?? [] as $index => $item)
                        <div class="col-span-12 flex items-center gap-2">
                            <div class="flex-none">
                                <input type="checkbox" 
                                       wire:model="taskData.checklist.{{ $index }}.completed"
                                       class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                            </div>
                            <div class="flex-1">
                                <x-form-elements.input 
                                    wire:model="taskData.checklist.{{ $index }}.text"
                                    placeholder="Görev..."
                                />
                            </div>
                            <div class="flex-none">
                                <button type="button" 
                                        wire:click="removeChecklistItem({{ $index }})"
                                        class="text-red-600 hover:text-red-800">
                                    <x-heroicon-m-trash class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                    
                    <button type="button" 
                            wire:click="addChecklistItem"
                            class="text-sm text-primary-600 hover:text-primary-800">
                        <div class="flex items-center gap-1">
                            <x-heroicon-m-plus class="w-4 h-4" />
                            <span>Madde Ekle</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <x-button.base type="button" color="white" wire:click="closeTaskModal">
                    İptal
            </x-button.base>
            <x-form-elements.button 
                type="submit">
                Kaydet
            </x-form-elements.button>
        </div>
    </form>
</x-modal> 