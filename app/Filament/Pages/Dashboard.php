<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use BackedEnum;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Panel Administrativo';
    
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-home';
    
    public function getTitle(): string
    {
        return 'Panel Administrativo';
    }
}