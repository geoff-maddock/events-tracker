<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A remote image could not be fetched safely. The message is intended to be
 * shown to the API caller (it never includes internal details).
 */
class RemoteImageException extends RuntimeException
{
}
