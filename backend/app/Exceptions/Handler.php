<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'code',
        'two_factor_disable_password',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // 413: Body too large (often file upload / large form payload)
        if ($e instanceof PostTooLargeException || ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 413)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Payload too large. Please reduce the file size and try again.',
                ], 413);
            }

            return response()->view('errors.413', [], 413);
        }

        // في حال انتهاء صلاحية الـ CSRF (خطأ 419)، نعيد توجيه المستخدم لصفحة تسجيل الدخول
        if ($e instanceof TokenMismatchException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session has expired. Please refresh the page and try again.',
                ], 419);
            }

            // لو المسار في لوحة الإدارة → إعادة توجيه لتسجيل دخول الأدمن
            if ($request->is('admin') || $request->is('admin/*')) {
                return redirect()
                    ->route('admin.login')
                    ->with('error', 'انتهت صلاحية الجلسة، يرجى تسجيل الدخول مرة أخرى.');
            }

            // باقي الواجهات → تسجيل دخول المستخدم العادي
            return redirect()
                ->route('login')
                ->with('error', 'انتهت صلاحية الجلسة، يرجى تسجيل الدخول مرة أخرى.');
        }

        return parent::render($request, $e);
    }
}
