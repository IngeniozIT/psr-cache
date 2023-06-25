<?php

declare(strict_types=1);

namespace IngeniozIT\Cache;

use Psr\SimpleCache\CacheInterface;
use Psr\Cache\CacheItemPoolInterface;
use DateInterval;

final readonly class SimpleCache implements CacheInterface
{
    public function __construct(
        private CacheItemPoolInterface $pool,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $item = $this->pool->getItem($key);
        return $item->isHit() ? $item->get() : $default;
    }

    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $item = $this->pool->getItem($key);
        $item->set($value);
        $item->expiresAfter($ttl);
        return $this->pool->save($item);
    }

    public function delete(string $key): bool
    {
        return $this->pool->deleteItem($key);
    }

    public function clear(): bool
    {
        return $this->pool->clear();
    }

    /**
     * @param iterable<string> $keys
     * @return array<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];
        foreach ($keys as $key) {
            $item = $this->pool->getItem($key);
            $values[$key] = $item->isHit() ? $item->get() : $default;
        }
        return $values;
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $item = $this->pool->getItem($key);
            $item->set($value);
            $item->expiresAfter($ttl);
            $this->pool->saveDeferred($item);
        }
        return $this->pool->commit();
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->pool->deleteItem($key)) {
                $success = false;
            }
        }
        return $success;
    }

    public function has(string $key): bool
    {
        return $this->pool->hasItem($key);
    }
}
