<x-modal 
    wire:model="showListModal"
    x-on:close="$wire.closeListModal()"
>
    <x-slot name="title">
        Liste Oluştur
    </x-slot>

    <form wire:submit="createList">
        <div class="space-y-4">
            <x-form-elements.input 
                wire:model="listData.name"
                label="Liste Adı"
                required
                placeholder="Örn: Yapılacaklar, Devam Edenler, Tamamlananlar"
            />

            <div class="flex justify-end gap-3">
                <x-button.base type="button" color="white" wire:click="closeListModal">
                    İptal
                </x-button.base>
                <x-button.base type="submit">
                    Kaydet
                </x-button.base>
            </div>
        </div>
    </form>
</x-modal> 