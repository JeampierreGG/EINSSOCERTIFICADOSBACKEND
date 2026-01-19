<x-filament-panels::page>
    <form wire:submit="create">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-x-3">
            <x-filament::button type="button" color="gray" wire:click="cancel" tag="a" :href="\App\Filament\Admin\Resources\PaymentCompletedResource::getUrl('index')">
                Cancelar
            </x-filament::button>

            <x-filament::button type="submit">
                Generar Certificado
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
