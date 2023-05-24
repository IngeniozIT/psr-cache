<?php

declare(strict_types=1);

namespace IngeniozIT\Cache\Tests;

use IngeniozIT\Cache\{CacheItem, FileCacheItemPool, InvalidArgumentException};
use Psr\Cache\CacheItemPoolInterface;
use IngeniozIT\Clock\SystemClock;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileCacheItemPoolTest extends CacheItemPoolTestAbstract
{
    private string $cacheDir;

    public function setUp(): void
    {
        $this->cacheDir = __DIR__ . '/../tmp/tests/FileCacheItemPool' . uniqid();
    }

    public function tearDown(): void
    {
        if (!is_dir($this->cacheDir)) {
            return;
        }

        $dir = new RecursiveDirectoryIterator($this->cacheDir, FilesystemIterator::SKIP_DOTS);
        $dir = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST);

        /** @var \SplFileInfo $file */
        foreach ($dir as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
                continue;
            }
            unlink($file->getPathname());
        }
        rmdir($this->cacheDir);
    }

    protected function getPool(): CacheItemPoolInterface
    {
        return new FileCacheItemPool($this->cacheDir, new SystemClock());
    }

    /**
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     */
    public function testCannotInitializePoolWithInvalidDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        @new FileCacheItemPool('/*/', new SystemClock());
    }

    public function testPlacesItemsInFileSystem(): void
    {
        $pool = $this->getPool();
        $item = new CacheItem(
            key: 'item/Key',
            value: 'value',
            expirationDate: null,
            clock: new SystemClock(),
        );

        $pool->save($item);

        self::assertTrue(is_dir($this->cacheDir));
        self::assertTrue(is_dir($this->cacheDir . '/item/'));
        self::assertTrue(file_exists($this->cacheDir . '/item/Key.cache'));
    }

    public function testCleansUpFileSystemOnClear(): void
    {
        $pool = $this->getPool();
        $item = new CacheItem(
            key: 'item/Key',
            value: 'value',
            expirationDate: null,
            clock: new SystemClock(),
        );

        $pool->save($item);
        $pool->clear();

        self::assertTrue(is_dir($this->cacheDir));
        self::assertFalse(is_dir($this->cacheDir . '/item/'));
        self::assertFalse(file_exists($this->cacheDir . '/item/Key.cache'));
    }
}
