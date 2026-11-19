<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Something the site's own checks found wrong: a scheduled command that
 * failed, a queue nobody is working, an access token about to lapse.
 *
 * It is only ever passed to report(), never thrown, so it reaches Sentry and
 * the log through the same path as any other error.
 */
class MonitoringAlert extends RuntimeException
{
}
