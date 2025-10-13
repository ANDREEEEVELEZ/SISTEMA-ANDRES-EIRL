<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Verificar si el usuario está inactivo
            if ($user->estado === 'inactivo') {
                Auth::logout();
                
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return redirect()->route('filament.admin.auth.login')
                    ->with('error', 'Su cuenta ha sido desactivada. Contacte al administrador.');
            }
        }
        
        return $next($request);
    }
}
