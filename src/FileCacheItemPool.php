<?php

declare(strict_types=1);

namespace IngeniozIT\Cache;

use Psr\Cache\{CacheItemPoolInterface, CacheItemInterface};
use Psr\Clock\ClockInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use DateTimeImmutable;
use FilesystemIterator;

final class FileCacheItemPool implements CacheItemPoolInterface
{
    /** @var array<string, CacheItemInterface> */
    private array $deferred = [];

    public function __construct(
        private readonly string $directory,
        private readonly ClockInterface $clock,
    ) {
        if (!is_dir($directory) && !mkdir(directory: $directory, recursive: true)) {
            throw new InvalidArgumentException('Could not create cache directory');
        }
    }

    private function getFilePath(string $key): string
    {
        return $this->directory . '/' . $key . '.cache';
    }

    public function getItem(string $key): CacheItemInterface
    {
        $item = $this->hasItem($key) ?
            unserialize($this->getFileContent($key)) :
            null;
        return $item instanceof CacheItemInterface ?
            $item :
            new CacheItem($key, null, new DateTimeImmutable('1970-01-01'), $this->clock);
    }

    private function getFileContent(string $key): string
    {
        return file_get_contents($this->getFilePath($key)) ?: '';
    }

    /**
     * @param string[] $keys
     * @return iterable<string, CacheItemInterface>
     */
    public function getItems(array $keys = []): iterable
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }
        return $items;
    }

    public function hasItem(string $key): bool
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Key must be a string');
        }
        return file_exists($this->getFilePath($key));
    }

    public function clear(): bool
    {
        $this->removeDir($this->directory);

        return true;
    }

    private function removeDir(string $target): void
    {
        $dir = new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS);
        $dir = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST);

        /** @var \SplFileInfo $file */
        foreach ($dir as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
                continue;
            }
            unlink($file->getPathname());
        }
    }

    public function deleteItem(string $key): bool
    {
        return !$this->hasItem($key) || unlink($this->getFilePath($key));
    }

    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->deleteItem($key);
        }

        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        $dirname = dirname($this->getFilePath($item->getKey()));
        return $item->isHit() &&
            (is_dir($dirname) || mkdir(directory: $dirname, recursive: true)) &&
            file_put_contents($this->getFilePath($item->getKey()), serialize($item));
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    public function commit(): bool
    {
        foreach ($this->deferred as $key => $item) {
            $this->save($item);
            unset($this->deferred[$key]);
        }

        return true;
    }
}
