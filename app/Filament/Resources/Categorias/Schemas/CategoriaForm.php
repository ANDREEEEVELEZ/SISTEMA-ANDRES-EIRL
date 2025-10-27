<?php

namespace App\Filament\Resources\Categorias\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CategoriaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('NombreCategoria')
                    ->label('Nombre de la Categoría')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                // Toggle de estado - Solo visible al editar y solo para super_admin
                Toggle::make('estado')
                    ->label('Estado')
                    ->default(true)
                    ->onColor('success')
                    ->offColor('danger')
                    ->helperText('Activo: La categoría está disponible | Inactivo: La categoría no está disponible')
                    ->visible(fn ($livewire) => 
                        $livewire instanceof \App\Filament\Resources\Categorias\Pages\EditCategoria 
                        && Auth::check() && Auth::user()?->hasRole('super_admin')
                    ),

                // Placeholder para usuarios no super_admin - Solo visible al editar
                Placeholder::make('estado_info')
                    ->label('Estado')
                    ->content(fn ($record) => 
                        $record?->estado 
                            ? '✅ Activo (' . $record->productos()->count() . ' productos asociados)' 
                            : '❌ Inactivo'
                    )
                    ->visible(fn ($livewire) => 
                        $livewire instanceof \App\Filament\Resources\Categorias\Pages\EditCategoria 
                        && Auth::check() && !Auth::user()?->hasRole('super_admin')
                    ),
            ]);
    }
}
