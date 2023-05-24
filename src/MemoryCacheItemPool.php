<?php

declare(strict_types=1);

namespace IngeniozIT\Cache;

use Psr\Cache\{CacheItemPoolInterface, CacheItemInterface};
use Psr\Clock\ClockInterface;
use DateTimeImmutable;

final class MemoryCacheItemPool implements CacheItemPoolInterface
{
    /** @var array<string, CacheItemInterface> */
    private array $items = [];
    /** @var array<string, CacheItemInterface> */
    private array $deferred = [];

    public function __construct(
        private readonly ClockInterface $clock,
    ) {
    }

    public function getItem(string $key): CacheItemInterface
    {
        return $this->hasItem($key) ?
            $this->items[$key] :
            new CacheItem($key, null, new DateTimeImmutable('1970-01-01'), $this->clock);
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
        return isset($this->items[$key]);
    }

    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    public function deleteItem(string $key): bool
    {
        if ($this->hasItem($key)) {
            unset($this->items[$key]);
        }

        return true;
    }

    /**
     * @param string[] $keys
     */
    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->deleteItem($key);
        }

        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        if (!$item->isHit()) {
            return false;
        }

        $this->items[$item->getKey()] = $item;
        return true;
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
