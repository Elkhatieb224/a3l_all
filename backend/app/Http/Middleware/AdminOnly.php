<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * Handle an incoming request.
     * Only Admins and Super Admins can access routes protected by this middleware.
     * Moderators are not allowed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $admin = auth('admin')->user();

        if (!$admin->is_active) {
            auth('admin')->logout();
            return redirect()->route('admin.login')
                ->with('error', 'حسابك غير نشط. يرجى التواصل مع الإدارة.');
        }

        if (!$admin->isAdmin()) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة. يجب أن تكون Admin أو Super Admin.');
        }

        return $next($request);
    }
}

