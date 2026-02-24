<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SystemSettingResource\Pages;
use App\Filament\Admin\Resources\SystemSettingResource\RelationManagers;
use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SystemSettingResource extends Resource
{
    protected static ?string $model = SystemSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configuración del Sistema';
    protected static ?string $navigationGroup = 'Ajustes';
    protected static ?int $navigationSort = 0;

    protected static ?string $modelLabel = 'Configuración del Sistema';
    protected static ?string $pluralModelLabel = 'Configuración del Sistema';
    protected static ?string $breadcrumb = 'Configuración del Sistema';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Logos')
                    ->schema([
                        Forms\Components\FileUpload::make('header_logo')
                            ->label('Logo del Encabezado')
                            ->image()
                            ->disk('s3')
                            ->directory('system')
                            ->visibility('public')
                            ->columnSpan(1),
                        Forms\Components\FileUpload::make('footer_logo')
                            ->label('Logo del Footer')
                            ->image()
                            ->disk('s3')
                            ->directory('system')
                            ->visibility('public')
                            ->columnSpan(1),
                        Forms\Components\FileUpload::make('loading_logo')
                            ->label('Logo de Carga (Spinner)')
                            ->image()
                            ->disk('s3')
                            ->directory('system')
                            ->visibility('public')
                            ->columnSpan(1),
                    ])->columns(3),

                Forms\Components\Section::make('Redes Sociales')
                    ->schema([
                        Forms\Components\TextInput::make('facebook_url')
                            ->label('Facebook URL')
                            ->url()
                            ->prefixIcon('heroicon-o-link'),
                        Forms\Components\TextInput::make('instagram_url')
                            ->label('Instagram URL')
                            ->url()
                            ->prefixIcon('heroicon-o-link'),
                        Forms\Components\TextInput::make('tiktok_url')
                            ->label('TikTok URL')
                            ->url()
                            ->prefixIcon('heroicon-o-link'),
                        Forms\Components\TextInput::make('youtube_url')
                            ->label('YouTube URL')
                            ->url()
                            ->prefixIcon('heroicon-o-link'),
                        Forms\Components\TextInput::make('x_url')
                            ->label('X (Twitter) URL')
                            ->url()
                            ->prefixIcon('heroicon-o-link'),
                    ])->columns(2),

                Forms\Components\Section::make('Información de Contacto')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Dirección')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->maxLength(255),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        // No se usa tabla, siempre redirige al formulario de edición
        return $table->columns([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSystemSettings::route('/'),
        ];
    }    
}
