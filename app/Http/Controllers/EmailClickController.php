<?php

namespace App\Http\Controllers;

use App\Services\DigestEngagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A click on a site link in a member's email (#2083): note that they read
 * it, then send them on to the page.
 *
 * Not behind the `signed` middleware: a link mangled by a mail client should
 * still take the reader somewhere. The signature only decides whether the
 * click is credited. The destination is only ever a path on this site, so
 * the route can't be used as an open redirect.
 */
class EmailClickController extends Controller
{
    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $to = $request->query('to');
        $target = is_string($to) && str_starts_with($to, '/') && !str_starts_with($to, '//') && !str_contains($to, '\\')
            ? $to
            : '/';

        // HEAD is what some link checkers send; only a real visit counts
        if ($request->isMethod('GET') && $request->hasValidSignature()) {
            DigestEngagement::recordEmailClick($id);
        }

        return redirect()->to($target);
    }
}
