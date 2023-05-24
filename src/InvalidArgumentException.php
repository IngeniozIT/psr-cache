<?php

declare(strict_types=1);

namespace IngeniozIT\Cache;

use Psr\Cache\InvalidArgumentException as PsrInvalidArgumentException;

final class InvalidArgumentException extends CacheException implements PsrInvalidArgumentException
{
}
