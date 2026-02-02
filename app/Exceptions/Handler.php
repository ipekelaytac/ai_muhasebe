<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            // Log all exceptions with context
            \Log::error('Exception occurred', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        });
    }
    
    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // For web requests, show user-friendly error messages
        if ($request->expectsJson()) {
            return $this->handleJsonException($request, $e);
        }
        
        // For web UI, show friendly messages
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return parent::render($request, $e);
        }
        
        // Show friendly error page for production
        if (!config('app.debug')) {
            return response()->view('errors.generic', [
                'message' => $this->getUserFriendlyMessage($e)
            ], 500);
        }
        
        return parent::render($request, $e);
    }
    
    /**
     * Handle JSON exceptions
     */
    protected function handleJsonException($request, Throwable $e)
    {
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        
        return response()->json([
            'success' => false,
            'error' => $this->getUserFriendlyMessage($e),
            'message' => $this->getUserFriendlyMessage($e),
        ], $statusCode);
    }
    
    /**
     * Get user-friendly error message
     */
    protected function getUserFriendlyMessage(Throwable $e): string
    {
        // Map common exceptions to Turkish messages
        $message = $e->getMessage();
        
        // If message is already in Turkish or contains Turkish characters, return as-is
        if (preg_match('/[çğıöşüÇĞIİÖŞÜ]/u', $message)) {
            return $message;
        }
        
        // Map common English exceptions to Turkish
        $translations = [
            'Unauthenticated' => 'Oturum açmanız gerekiyor.',
            'Unauthorized' => 'Bu işlem için yetkiniz yok.',
            'Model not found' => 'Kayıt bulunamadı.',
            'Too many attempts' => 'Çok fazla deneme yaptınız. Lütfen daha sonra tekrar deneyin.',
            'SQLSTATE' => 'Veritabanı hatası oluştu. Lütfen tekrar deneyin.',
        ];
        
        foreach ($translations as $key => $translation) {
            if (str_contains($message, $key)) {
                return $translation;
            }
        }
        
        // Default message
        if (config('app.debug')) {
            return $message;
        }
        
        return 'Bir hata oluştu. Lütfen tekrar deneyin veya sistem yöneticisine başvurun.';
    }
}
