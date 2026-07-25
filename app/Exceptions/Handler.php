<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
            //
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
        // Intercept 403 Forbidden / Authorization Exceptions
        $isForbidden = $e instanceof UnauthorizedException
            || $e instanceof AuthorizationException
            || ($e instanceof HttpException && $e->getStatusCode() === 403);

        if ($isForbidden) {
            // Return JSON response for API routes
            if ($request->expectsJson() || $request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'status'  => 403,
                    'error'   => 'Forbidden',
                    'message' => 'You do not have permission to access this page.',
                ], 403);
            }

            // Render custom 403 view for Web routes
            return response()->view('errors.403', [
                'exception' => $e,
            ], 403);
        }

        return parent::render($request, $e);
    }
}
