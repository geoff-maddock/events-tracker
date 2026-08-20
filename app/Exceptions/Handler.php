<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array<int,class-string<Throwable>>
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            \Sentry\Laravel\Integration::captureUnhandledException($e);
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof ModelNotFoundException) {
            abort(404);
        }

        // A stale page (open past the session lifetime) posts a CSRF token that
        // no longer matches anything. Instead of a dead-end 419, send the user
        // back with a fresh session so a retry works without a manual reload.
        if ($e instanceof TokenMismatchException && !$request->expectsJson()) {
            return redirect()->back(fallback: route('login'))
                ->withInput($request->except('password', 'password_confirmation', '_token'))
                ->with('error', 'Your session expired. Please try again.');
        }

        return parent::render($request, $e);
    }
}
