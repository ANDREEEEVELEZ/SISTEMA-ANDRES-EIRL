<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $path = ltrim($request->path(), '/');

        // Permitir acceso a la página de login/password reset y assets públicos de Filament
        $allowedPrefixes = [
            'admin/login',
            'admin/password',
            'admin/_assets',
            'admin/livewire',
            'admin/notifications',
            'admin/manifest.json',
        ];

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($path, ltrim($prefix, '/'))) {
                return $next($request);
            }
        }

        if (Auth::check() && optional(Auth::user())->hasRole('super_admin')) {
            return $next($request);
        }


        if (! Auth::check()) {
            return $next($request);
        }


        return redirect('/');
    }
}
