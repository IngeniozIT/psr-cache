<?php

declare(strict_types=1);

namespace IngeniozIT\Cache;

use Exception;
use Psr\Cache\CacheException as PsrCacheException;
use Psr\SimpleCache\CacheException as PsrSimpleCacheException;

class CacheException extends Exception implements PsrCacheException, PsrSimpleCacheException
{
}
