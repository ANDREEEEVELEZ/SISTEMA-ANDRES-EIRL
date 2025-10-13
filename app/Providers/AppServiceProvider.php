<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Verificar el estado del usuario al intentar iniciar sesión
        Event::listen(Attempting::class, function ($event) {
            $credentials = $event->credentials;
            
            // Buscar el usuario por email
            $user = \App\Models\User::where('email', $credentials['email'] ?? '')->first();
            
            // Si el usuario existe y está inactivo, prevenir el login
            if ($user && $user->estado === 'inactivo') {
                throw ValidationException::withMessages([
                    'email' => 'Su cuenta ha sido desactivada. Por favor, contacte al administrador del sistema.',
                ]);
            }
        });
    }
}
