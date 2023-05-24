<?php

namespace IngeniozIT\Cache\Tests;

use DateInterval;
use PHPUnit\Framework\TestCase;
use IngeniozIT\Cache\{CacheItem, InvalidArgumentException};
use IngeniozIT\Clock\SystemClock;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class CacheItemPoolTestAbstract extends TestCase
{
    abstract protected function getPool(): CacheItemPoolInterface;

    public function testIsAPsrCacheItemPool(): void
    {
        $pool = $this->getPool();

        $this->assertInstanceOf(CacheItemPoolInterface::class, $pool);
    }

    public function testCanHandleACacheItem(): void
    {
        $pool = $this->getPool();
        $clock = new SystemClock();
        $item = new CacheItem(
            key: 'itemKey',
            value: 'value',
            expirationDate: null,
            clock: $clock,
        );

        $saved = $pool->save($item);
        $hasItem = $pool->hasItem('itemKey');
        $fetchedItem = $pool->getItem('itemKey');

        $this->assertTrue($saved);
        $this->assertTrue($hasItem);
        $this->assertEquals('itemKey', $fetchedItem->getKey());
        $this->assertEquals('value', $fetchedItem->get());
        $this->assertEquals(true, $fetchedItem->isHit());
    }

    public function testCanHandleANonExistingCacheItem(): void
    {
        $pool = $this->getPool();

        $hasItem = $pool->hasItem('itemKey');
        $fetchedItem = $pool->getItem('itemKey');

        $this->assertFalse($hasItem);
        $this->assertEquals('itemKey', $fetchedItem->getKey());
        $this->assertNull($fetchedItem->get());
        $this->assertFalse($fetchedItem->isHit());
    }

    public function testCannotAccessInvalidCacheItem(): void
    {
        $pool = $this->getPool();

        $this->expectException(InvalidArgumentException::class);
        $pool->hasItem('');
    }

    public function testCannotCheckIfPoolHasInvalidCacheItem(): void
    {
        $pool = $this->getPool();

        $this->expectException(InvalidArgumentException::class);
        $pool->hasItem('');
    }

    public function testCanGetMultipleCacheItemsAtOnce(): void
    {
        $pool = $this->getPool();
        $item1 = new CacheItem(
            key: 'itemKey1',
            value: 'value1',
            expirationDate: null,
            clock: new SystemClock(),
        );
        $item2 = new CacheItem(
            key: 'itemKey2',
            value: 'value2',
            expirationDate: null,
            clock: new SystemClock(),
        );
        $expectedItems = [
            'itemKey1' => $item1,
            'itemKey2' => $item2,
        ];

        $pool->save($item1);
        $pool->save($item2);
        $items = $pool->getItems(['itemKey1', 'itemKey2']);

        self::assertEquals($expectedItems, $items);
    }

    public function testCanRemoveAllItems(): void
    {
        $pool = $this->getPool();
        $item1 = new CacheItem(
            key: 'itemKey1',
            value: 'value1',
            expirationDate: null,
            clock: new SystemClock(),
        );
        $item2 = new CacheItem(
            key: 'itemKey',
            value: 'value2',
            expirationDate: null,
            clock: new SystemClock(),
        );
        $item3 = new CacheItem(
            key: 'item2Key',
            value: 'value3',
            expirationDate: null,
            clock: new SystemClock(),
        );

        $pool->save($item1);
        $pool->save($item2);
        $pool->save($item3);
        $cleared = $pool->clear();
        $hasItem1 = $pool->hasItem('itemKey1');
        $hasItem2 = $pool->hasItem('itemKey');
        $hasItem3 = $pool->hasItem('item2Key3');

        self::assertTrue($cleared);
        self::assertFalse($hasItem1);
        self::assertFalse($hasItem2);
        self::assertFalse($hasItem3);
    }

    public function testCanRemoveOneItem(): void
    {
        $pool = $this->getPool();
        $item1 = new CacheItem(
            key: 'itemKey1',
            value: 'value1',
            expirationDate: null,
            clock: new SystemClock(),
        );

        $pool->save($item1);
        $cleared = $pool->deleteItem('itemKey1');
        $hasItem1 = $pool->hasItem('itemKey1');

        self::assertTrue($cleared);
        self::assertFalse($hasItem1);
    }

    public function testCanRemoveMultipleItems(): void
    {
        $pool = $this->getPool();
        $item1 = new CacheItem(
            key: 'itemKey1',
            value: 'value1',
            expirationDate: null,
            clock: new SystemClock(),
        );
        $item2 = new CacheItem(
            key: 'itemKey2',
            value: 'value2',
            expirationDate: null,
            clock: new SystemClock(),
        );

        $pool->save($item1);
        $pool->save($item2);
        $cleared = $pool->deleteItems(['itemKey1', 'itemKey2']);
        $hasItem1 = $pool->hasItem('itemKey1');
        $hasItem2 = $pool->hasItem('itemKey2');

        self::assertTrue($cleared);
        self::assertFalse($hasItem1);
        self::assertFalse($hasItem2);
    }

    public function testCanDeferItemsSave(): void
    {
        $pool = $this->getPool();
        $item1 = new CacheItem(
            key: 'itemKey1',
            value: 'value1',
            expirationDate: null,
            clock: new SystemClock(),
        );

        $deffered = $pool->saveDeferred($item1);
        $hasItem1 = $pool->hasItem('itemKey1');
        $commited = $pool->commit();
        $hasItem1AfterCommit = $pool->hasItem('itemKey1');

        self::assertTrue($deffered);
        self::assertTrue($commited);
        self::assertFalse($hasItem1);
        self::assertTrue($hasItem1AfterCommit);
    }

    public function testDoesNotSaveOutdatedItems(): void
    {
        $pool = $this->getPool();
        $clock = new SystemClock();
        $item1 = new CacheItem(
            key: 'itemKey1',
            value: 'value1',
            expirationDate: $clock->now()->sub(new DateInterval('PT1S')),
            clock: $clock,
        );

        $saved = $pool->save($item1);
        $hasItem1 = $pool->hasItem('itemKey1');

        self::assertFalse($saved);
        self::assertFalse($hasItem1);
    }
}
