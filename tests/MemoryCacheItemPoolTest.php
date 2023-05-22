<?php

declare(strict_types=1);

namespace IngeniozIT\Cache\Tests;

use IngeniozIT\Cache\MemoryCacheItemPool;
use IngeniozIT\Clock\SystemClock;
use Psr\Cache\CacheItemPoolInterface;

class MemoryCacheItemPoolTest extends CacheItemPoolTestAbstract
{
    protected function getPool(): CacheItemPoolInterface
    {
        return new MemoryCacheItemPool(new SystemClock());
    }
}
