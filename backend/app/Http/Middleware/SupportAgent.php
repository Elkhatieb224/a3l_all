<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupportAgent
{
    /**
     * Allow Super Admin, Admin, or Support Agent.
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

        if (!($admin->isSuperAdmin() || $admin->isAdmin() || $admin->isSupportAgent())) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة. يلزم أن تكون ضمن فريق الدعم أو الإدارة.');
        }

        return $next($request);
    }
}

