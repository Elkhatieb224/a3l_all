<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTwoFactorPending
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('admin_2fa_pending_id')) {
            return redirect()->route('admin.login');
        }

        $adminId = (int) $request->session()->get('admin_2fa_pending_id');
        if (! Admin::where('id', $adminId)->where('two_factor_enabled', true)->exists()) {
            $request->session()->forget(['admin_2fa_pending_id', 'admin_2fa_remember', 'admin_2fa_failed']);

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
