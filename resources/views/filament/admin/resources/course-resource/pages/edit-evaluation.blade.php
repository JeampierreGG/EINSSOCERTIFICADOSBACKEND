<x-filament::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-4 flex justify-end gap-x-2">
            <x-filament::button color="gray" tag="a" :href="\App\Filament\Admin\Resources\CourseResource::getUrl('modules', ['record' => $record])">
                Cancelar
            </x-filament::button>

            <x-filament::button type="submit">
                Actualizar Evaluación
            </x-filament::button>
        </div>
    </form>
</x-filament::page>
