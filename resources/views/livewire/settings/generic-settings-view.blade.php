<div>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end"> {{-- Butonu sağa yaslamak için flex ve justify-end eklendi --}}
            <x-filament::button type="submit">
                Ayarları Kaydet
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />
</div>