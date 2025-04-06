<x-modal wire:model="showEditListModal">
    <x-slot name="title">
        Liste Düzenle
    </x-slot>

    <form wire:submit="updateList">
        <div class="space-y-4">
            <x-form-elements.input 
                wire:model="listData.name"
                label="Liste Adı"
                required
                placeholder="Liste adı"
            />

            <div class="flex justify-end gap-3">
                <x-button.base type="button" color="white" wire:click="$set('showEditListModal', false)">
                    İptal
                </x-button.base>
                <x-button.base type="submit">
                    Güncelle
                </x-button.base>
            </div>
        </div>
    </form>
</x-modal> 