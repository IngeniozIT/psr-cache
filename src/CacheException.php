<?php

declare(strict_types=1);

namespace IngeniozIT\Cache;

use Exception;
use Psr\Cache\CacheException as PsrCacheException;

class CacheException extends Exception implements PsrCacheException
{
}
