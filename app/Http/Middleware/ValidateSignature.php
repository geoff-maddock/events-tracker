<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ValidateSignature as Middleware;

class ValidateSignature extends Middleware
{
    /**
     * Query string parameters left out of the signature check: the tracking
     * tags mail clients and link scanners add to a link on its way to the
     * browser. A signed link (the data export, unsubscribe, email
     * verification) must still work with them attached.
     *
     * @var array<int, string>
     */
    protected $except = [
        'fbclid',
        'gclid',
        'mc_cid',
        'mc_eid',
        'utm_campaign',
        'utm_content',
        'utm_medium',
        'utm_source',
        'utm_term',
    ];
}
