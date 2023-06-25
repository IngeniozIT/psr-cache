<?php

declare(strict_types=1);

namespace IngeniozIT\Cache;

use Psr\Cache\InvalidArgumentException as PsrCacheInvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as PsrSimpleCacheInvalidArgumentException;

final class InvalidArgumentException extends CacheException implements PsrCacheInvalidArgumentException, PsrSimpleCacheInvalidArgumentException
{
}
