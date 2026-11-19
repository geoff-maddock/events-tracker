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
    /**
     * Report a problem. Every alert of one kind has the same stack trace, so
     * the key is what keeps Sentry from filing a failed sitemap run under an
     * older issue about a failed digest.
     */
    public static function send(string $key, string $message): void
    {
        \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($key, $message) {
            $scope->setFingerprint(['monitoring-alert', $key]);

            report(new self($message));
        });
    }
}
