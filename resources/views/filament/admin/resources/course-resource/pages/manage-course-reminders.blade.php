<div>
    <x-filament-panels::page>

        {{-- ============================================================
             CABECERA INFORMATIVA
             ============================================================ --}}
        <div class="mb-6 p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-900">
                    <x-heroicon-o-bell class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div class="flex-1">
                    <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">
                        Imágenes para Correos de Recordatorio
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Sube aquí las imágenes que se incluirán en los correos automáticos que reciben los estudiantes.
                        Cada sección corresponde a un correo diferente. Los bloques de evaluación se generan automáticamente
                        según las evaluaciones creadas en la sección de <strong>Módulos</strong>.
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                            <x-heroicon-o-check-badge class="w-3.5 h-3.5" /> Correo 1: Confirmación de Matrícula
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                            <x-heroicon-o-rocket-launch class="w-3.5 h-3.5" /> Correo 2: Apertura del Curso
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300">
                            <x-heroicon-o-clipboard-document-list class="w-3.5 h-3.5" /> Correo 3: Evaluación (por evaluación)
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300">
                            <x-heroicon-o-bell-alert class="w-3.5 h-3.5" /> Correo 4: Recordatorio de Evaluación (por evaluación)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        @if($this->evaluations->isEmpty())
            <div class="mb-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-300">
                            Sin evaluaciones — solo aparecerán los correos 1 y 2
                        </p>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                            Los correos 3 y 4 se configuran cuando el curso tiene evaluaciones creadas.
                            <a href="{{ \App\Filament\Admin\Resources\CourseResource::getUrl('modules', ['record' => $record->id]) }}"
                               class="font-semibold underline hover:text-amber-800 dark:hover:text-amber-200 ml-1">
                                Ir a Módulos →
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Formulario Filament --}}
        <x-filament-panels::form wire:submit="saveEnrollment">
            {{ $this->form }}
        </x-filament-panels::form>

    </x-filament-panels::page>
</div>
