<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // If authenticated using admin guard, go to admin dashboard
                if ($guard === 'admin' || Auth::guard($guard)->user()?->is_super_admin) {
                    return redirect()->route('admin.dashboard');
                }

                // Fallback for other guards (you can change '/home' to a named route)
                return redirect('/home');
            }
        }

        return $next($request);
    }
}
