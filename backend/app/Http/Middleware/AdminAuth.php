<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        if (!Auth::guard('admin')->user()->is_active) {
            Auth::guard('admin')->logout();
            return redirect()->route('admin.login')
                ->with('error', 'حسابك غير نشط. يرجى التواصل مع الإدارة.');
        }

        return $next($request);
    }
}

