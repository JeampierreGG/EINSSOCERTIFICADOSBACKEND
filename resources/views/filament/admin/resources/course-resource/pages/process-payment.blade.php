<x-filament-panels::page>
    <form wire:submit="create">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-x-3">
            <x-filament::button type="button" color="gray" tag="a" :href="$this->getCancelUrl()">
                Cancelar
            </x-filament::button>

            <x-filament::button type="submit">
                Generar Certificado
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
