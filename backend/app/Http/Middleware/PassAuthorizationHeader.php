<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the Authorization header is available to Laravel when the web server
 * (e.g. Apache with PHP-FPM/CGI) does not pass it to PHP by default.
 * Without this, Bearer token auth returns 401 on many shared/production servers.
 */
class PassAuthorizationHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->header('Authorization') && !$request->header('authorization')) {
            $auth = $_SERVER['HTTP_AUTHORIZATION']
                ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                ?? $request->header('X-Authorization')
                ?? null;
            if ($auth !== null && $auth !== '') {
                // Ensure Bearer prefix if X-Authorization was used without it
                if (stripos($auth, 'Bearer ') !== 0) {
                    $auth = 'Bearer ' . trim($auth);
                }
                $request->headers->set('Authorization', $auth);
            }
        }

        return $next($request);
    }
}
