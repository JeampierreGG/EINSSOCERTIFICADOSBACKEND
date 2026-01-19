<?php

namespace App\Filament\Admin\Resources\TeacherResource\Pages;

use App\Filament\Admin\Resources\TeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeacher extends EditRecord
{
    protected static string $resource = TeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return TeacherResource::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $userName = $data['user_name'] ?? null;

        if ($userName && $this->record->user) {
            $this->record->user->update(['name' => $userName]);
        }
        
        unset($data['user_name']);

        return $data;
    }
}
