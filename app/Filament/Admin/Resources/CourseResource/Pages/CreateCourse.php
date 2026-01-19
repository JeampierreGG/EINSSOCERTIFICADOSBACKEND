<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Filament\Admin\Resources\CourseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate slug from title
        $data['slug'] = \Illuminate\Support\Str::slug($data['title']);
        
        // Generate code from initials of title
        $words = explode(' ', strtoupper($data['title']));
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= $word[0];
            }
        }
        
        // Check if code exists and add number if needed
        $baseCode = $initials;
        $code = $baseCode;
        $counter = 1;
        
        while (\App\Models\Course::where('code', $code)->exists()) {
            $code = $baseCode . $counter;
            $counter++;
        }
        
        $data['code'] = $code;
        
        return $data;
    }
}
