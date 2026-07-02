<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @return void
     *
     * @throws Exception
     */
    public function report(Throwable $e)
    {
        if (app()->bound('sentry') && $this->shouldReport($e)) {
            app('sentry')->captureException($e);
        }

        parent::report($e);
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

        // A malformed list query param (typically an unknown `sort` column, e.g.
        // /users?sort=start_at) reaches orderBy() and throws a QueryException,
        // which would otherwise surface as an opaque 500. Rather than fail hard,
        // strip the offending sort params, flash a notice, and redirect back so
        // the list renders in its default order. Kept deliberately narrow (GET
        // requests carrying sort/direction) so genuine DB errors on ordinary
        // pages are not masked. The exception is still reported (see report()).
        if ($e instanceof QueryException
            && $request->isMethod('get')
            && ($request->has('sort') || $request->has('direction'))
        ) {
            flash()->error(
                'Invalid sort',
                'That sort parameter is not valid; showing the default order instead.'
            );

            return redirect()->to($request->fullUrlWithoutQuery(['sort', 'direction']));
        }

        return parent::render($request, $e);
    }
}
