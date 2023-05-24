<?php

declare(strict_types=1);

namespace IngeniozIT\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Clock\ClockInterface;
use DateTimeInterface;
use DateInterval;

class CacheItem implements CacheItemInterface
{
    public function __construct(
        private readonly string $key,
        private mixed $value,
        private ?DateTimeInterface $expirationDate,
        private readonly ClockInterface $clock,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return $this->isHit() ? $this->value : null;
    }

    public function isHit(): bool
    {
        return $this->expirationDate === null || $this->clock->now() <= $this->expirationDate;
    }

    public function set(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function expiresAt(?DateTimeInterface $expiration): static
    {
        $this->expirationDate = $expiration;

        return $this;
    }

    public function expiresAfter(int|DateInterval|null $time): static
    {
        if (is_int($time)) {
            $time = new DateInterval("PT{$time}S");
        }
        return $this->expiresAt($time === null ? null : $this->clock->now()->add($time));
    }
}
