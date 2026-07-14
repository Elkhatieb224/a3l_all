<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdmin
{
    /**
     * Handle an incoming request.
     * Only Super Admins can access routes protected by this middleware.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('admin')->check()) {
            return redirect()->route('admin.login');
        }

        if (!auth('admin')->user()->isSuperAdmin()) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة. يجب أن تكون Super Admin.');
        }

        return $next($request);
    }
}

