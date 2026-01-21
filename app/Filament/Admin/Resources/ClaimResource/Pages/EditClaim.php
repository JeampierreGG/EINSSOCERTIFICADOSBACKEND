<?php

namespace App\Filament\Admin\Resources\ClaimResource\Pages;

use App\Filament\Admin\Resources\ClaimResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

use Illuminate\Support\Facades\Mail;
use App\Mail\ClaimResponseMail;
use Filament\Notifications\Notification;

class EditClaim extends EditRecord
{
    protected static string $resource = ClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $claim = $this->record;

        // Verificar si se ha ingresado una respuesta y si el email es válido
        if (!empty($claim->respuesta_admin) && filter_var($claim->email, FILTER_VALIDATE_EMAIL)) {
            try {
                // Enviamos el correo (podemos verificar si 'respuesta_admin' cambió usando $claim->wasChanged(), 
                // pero Filament refresca el modelo. Como regla de negocio simple, enviaremos si hay respuesta).
                // Para ser más estrictos y evitar spam en ediciones menores, verificamos si 'respuesta_admin' fue modificado
                // en la petición actual. Sin embargo, $claim ya está guardado.
                // Asumiremos que si el admin guarda en esta pantalla, quiere notificar la actualización.
                
                Mail::to($claim->email)->send(new ClaimResponseMail($claim));

                Notification::make()
                    ->title('Respuesta enviada por correo')
                    ->body("Se ha notificado a {$claim->email} correctamente.")
                    ->success()
                    ->send();

            } catch (\Exception $e) {
                Notification::make()
                    ->title('Error al enviar correo')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }
}
