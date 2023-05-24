<?php

declare(strict_types=1);

namespace IngeniozIT\Cache\Tests;

use IngeniozIT\Cache\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use IngeniozIT\Clock\{SystemClock, FrozenClock};
use IngeniozIT\Cache\CacheItem;
use Psr\Cache\CacheItemInterface;
use Psr\Clock\ClockInterface;
use DateTimeImmutable;
use DateInterval;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CacheItemTest extends TestCase
{
    public function testIsAPsrCacheItem(): void
    {
        $clock = $this->getClock();

        $item = new CacheItem(
            key: 'itemKey',
            value: null,
            expirationDate: null,
            clock: $clock,
        );

        self::assertInstanceOf(CacheItemInterface::class, $item);
    }

    public function testHasAKey(): void
    {
        $clock = $this->getClock();

        $item = new CacheItem(
            key: 'itemKey',
            value: null,
            expirationDate: null,
            clock: $clock,
        );
        $key = $item->getKey();

        $this->assertEquals('itemKey', $key);
    }

    /**
     * @dataProvider provideInvalidKeys
     * @phan-suppress PhanNoopNew
     */
    public function testKeyCannotContainReservedCharacters(string $key): void
    {
        $clock = $this->getClock();

        $this->expectException(InvalidArgumentException::class);
        new CacheItem(
            key: $key,
            value: null,
            expirationDate: null,
            clock: $clock,
        );
    }

    /**
     * @return array<string, array<string>>
     */
    public static function provideInvalidKeys(): array
    {
        return [
            '{' => ['{'],
            '}' => ['}'],
            '(' => ['('],
            ')' => [')'],
            '/' => ['/'],
            '\\' => ['\\'],
            '@' => ['@'],
            ':' => [':'],
        ];
    }

    public function testHasAValue(): void
    {
        $clock = $this->getClock();
        $item = new CacheItem(
            key: 'itemKey',
            value: 'value',
            expirationDate: null,
            clock: $clock,
        );

        $isHit = $item->isHit();
        $value = $item->get();

        $this->assertTrue($isHit);
        $this->assertEquals('value', $value);
    }

    public function testExpiredItemHasNoValue(): void
    {
        $clock = $this->getClock();
        $item = new CacheItem(
            key: 'itemKey',
            value: 'value',
            expirationDate: new DateTimeImmutable('1970-01-01'),
            clock: $clock,
        );

        $isHit = $item->isHit();
        $value = $item->get();

        $this->assertFalse($isHit);
        $this->assertNull($value);
    }

    public function testCanUpdateValue(): void
    {
        $clock = $this->getClock();
        $item = new CacheItem(
            key: 'itemKey',
            value: 'value',
            expirationDate: null,
            clock: $clock,
        );

        $item2 = $item->set('newValue');
        $value = $item->get();

        $this->assertSame($item, $item2);
        $this->assertEquals('newValue', $value);
    }

    public function testCanSetANewExpirationDate(): void
    {
        $clock = $this->getClock();
        $item = new CacheItem(
            key: 'itemKey',
            value: 'value',
            expirationDate: new DateTimeImmutable('1970-01-01'),
            clock: $clock,
        );

        $isHit = $item->isHit();
        $item2 = $item->expiresAt((new DateTimeImmutable())->modify('+1 second'));
        $isHit2 = $item2->isHit();
        sleep(1);
        $isHit3 = $item2->isHit();

        $this->assertFalse($isHit);
        $this->assertTrue($isHit2);
        $this->assertFalse($isHit3);
    }

    public function testCanRemoveTheExpirationDate(): void
    {
        $clock = $this->getClock();
        $item = new CacheItem(
            key: 'itemKey',
            value: 'value',
            expirationDate: new DateTimeImmutable('1970-01-01'),
            clock: $clock,
        );

        $isHit = $item->isHit();
        $item2 = $item->expiresAt(null);
        $isHit2 = $item2->isHit();

        $this->assertFalse($isHit);
        $this->assertTrue($isHit2);
    }

    public function testItemIsNotExpiredWhenTheDateIsExactlyTheExpirationDate(): void
    {
        $clock = new FrozenClock(new DateTimeImmutable());
        $item = new CacheItem(
            key: 'itemKey',
            value: 'value',
            expirationDate: $clock->now(),
            clock: $clock,
        );

        $isHit = $item->isHit();

        $this->assertTrue($isHit);
    }

    public function testCanIncreaseTheExpirationDateFromAnInt(): void
    {
        $clock = $this->getClock();
        $item = new CacheItem(
            key: 'itemKey',
            value: 'value',
            expirationDate: new DateTimeImmutable('1970-01-01'),
            clock: $clock,
        );

        $isHit = $item->isHit();
        $item2 = $item->expiresAfter(1);
        $isHit2 = $item2->isHit();
        sleep(1);
        $isHit3 = $item2->isHit();

        $this->assertFalse($isHit);
        $this->assertTrue($isHit2);
        $this->assertFalse($isHit3);
    }

    public function testCanIncreaseTheExpirationDateFromAnInterval(): void
    {
        $clock = $this->getClock();
        $item = new CacheItem(
            key: 'itemKey',
            value: 'value',
            expirationDate: new DateTimeImmutable('1970-01-01'),
            clock: $clock,
        );

        $isHit = $item->isHit();
        $item2 = $item->expiresAfter(new DateInterval('PT1S'));
        $isHit2 = $item2->isHit();
        sleep(1);
        $isHit3 = $item2->isHit();

        $this->assertFalse($isHit);
        $this->assertTrue($isHit2);
        $this->assertFalse($isHit3);
    }

    public function testCanRemoveTheExpirationDateAfter(): void
    {
        $clock = $this->getClock();
        $item = new CacheItem(
            key: 'itemKey',
            value: 'value',
            expirationDate: new DateTimeImmutable('1970-01-01'),
            clock: $clock,
        );

        $isHit = $item->isHit();
        $item2 = $item->expiresAfter(null);
        $isHit2 = $item2->isHit();

        $this->assertFalse($isHit);
        $this->assertTrue($isHit2);
    }

    protected function getClock(): ClockInterface
    {
        return new SystemClock();
    }
}
