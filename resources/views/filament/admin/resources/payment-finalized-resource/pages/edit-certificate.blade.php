<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-x-3">
            <x-filament::button type="button" color="gray" tag="a" :href="\App\Filament\Admin\Resources\PaymentFinalizedResource::getUrl('index')">
                Cancelar
            </x-filament::button>

            <x-filament::button type="submit">
                Guardar Cambios
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
