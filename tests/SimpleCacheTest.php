<?php

namespace IngeniozIT\Cache\Tests;

use PHPUnit\Framework\TestCase;
use IngeniozIT\Cache\SimpleCache;
use Psr\SimpleCache\{CacheInterface, InvalidArgumentException};
use IngeniozIT\Cache\MemoryCacheItemPool;
use IngeniozIT\Clock\SystemClock;

class SimpleCacheTest extends TestCase
{
    private function getSimpleCache(): CacheInterface
    {
        $cacheItemPool = new MemoryCacheItemPool(new SystemClock());
        return new SimpleCache($cacheItemPool);
    }

    public function testIsAPsrSimpleCache(): void
    {
        $cache = $this->getSimpleCache();

        $this->assertInstanceOf(CacheInterface::class, $cache);
    }

    public function testCanStoreValues(): void
    {
        $cache = $this->getSimpleCache();

        $saved = $cache->set('itemKey', 'value');
        $hasItem = $cache->has('itemKey');
        $fetchedItem = $cache->get('itemKey');

        $this->assertTrue($saved, 'Item has not been saved');
        $this->assertTrue($hasItem, 'The pool does not have the item');
        $this->assertEquals('value', $fetchedItem);
    }

    public function testCanStoreValuesWithTtl(): void
    {
        $cache = $this->getSimpleCache();

        $cache->set('itemKey1', 'value1', 1);
        $cache->setMultiple(['itemKey2' => 'value2'], 1);
        $hasItem1 = $cache->has('itemKey1');
        $hasItem2 = $cache->has('itemKey2');
        sleep(1);
        $hasItem1AfterTtl = $cache->has('itemKey1');
        $hasItem2AfterTtl = $cache->has('itemKey2');

        $this->assertTrue($hasItem1, 'The pool does not have the item1');
        $this->assertTrue($hasItem2, 'The pool does not have the item2');
        $this->assertFalse($hasItem1AfterTtl, 'The pool still has the item1');
        $this->assertFalse($hasItem2AfterTtl, 'The pool still has the item2');
    }

    public function testCanDeleteValues(): void
    {
        $cache = $this->getSimpleCache();

        $saved = $cache->set('itemKey', 'value');
        $deleted = $cache->delete('itemKey');
        $hasItem = $cache->has('itemKey');
        $fetchedItem = $cache->get('itemKey');

        $this->assertTrue($saved, 'Item has not been saved');
        $this->assertTrue($deleted, 'Item has not been deleted');
        $this->assertFalse($hasItem, 'The pool still has the item');
        $this->assertNull($fetchedItem);
    }

    public function testCanDeleteMultipleValues(): void
    {
        $cache = $this->getSimpleCache();

        $saved = $cache->setMultiple([
            'itemKey1' => 'value1',
            'itemKey2' => 'value2',
        ]);
        $deleted = $cache->deleteMultiple(['itemKey1', 'itemKey2']);
        $hasItem1 = $cache->has('itemKey1');
        $hasItem2 = $cache->has('itemKey2');
        $fetchedItem1 = $cache->get('itemKey1');
        $fetchedItem2 = $cache->get('itemKey2');

        $this->assertTrue($saved, 'Items have not been saved');
        $this->assertTrue($deleted, 'Items have not been deleted');
        $this->assertFalse($hasItem1, 'The pool still has the item1');
        $this->assertFalse($hasItem2, 'The pool still has the item2');
        $this->assertNull($fetchedItem1);
        $this->assertNull($fetchedItem2);
    }

    public function testCannotDeleteNonExistingValues(): void
    {
        $cache = $this->getSimpleCache();

        $cache->setMultiple([
            'itemKey1' => 'value1',
            'itemKey2' => 'value2',
        ]);
        $deleted = $cache->deleteMultiple(['itemKey1', 'nonExistingValue', 'itemKey2']);

        $this->assertFalse($deleted);
    }

    public function testCanClearAllCache(): void
    {
        $cache = $this->getSimpleCache();

        $saved = $cache->set('itemKey', 'value');
        $cleared = $cache->clear();
        $hasItem = $cache->has('itemKey');
        $fetchedItem = $cache->get('itemKey');

        $this->assertTrue($saved, 'Item has not been saved');
        $this->assertTrue($cleared, 'Cache has not been cleared');
        $this->assertFalse($hasItem, 'The pool still has the item');
        $this->assertNull($fetchedItem);
    }

    public function testCanGetMultipleValues(): void
    {
        $cache = $this->getSimpleCache();

        $saved = $cache->setMultiple([
            'itemKey1' => 'value1',
            'itemKey2' => 'value2',
        ]);
        $fetchedItems = $cache->getMultiple(['itemKey1', 'itemKey2', 'itemKey3']);

        $this->assertTrue($saved, 'Items have not been saved');
        $this->assertEquals([
            'itemKey1' => 'value1',
            'itemKey2' => 'value2',
            'itemKey3' => null,
        ], $fetchedItems);
    }

    /**
     * @dataProvider invalidKeysScenariosProvider
     */
    public function testCannotUseInvalidKeys(callable $scenario): void
    {
        $cache = $this->getSimpleCache();

        $this->expectException(InvalidArgumentException::class);
        $scenario($cache);
    }

    /**
     * @return array<string, array{0: callable}>
     */
    public static function invalidKeysScenariosProvider(): array
    {
        return [
            'get' => [
                function (SimpleCache $cache) {
                    $cache->get('{invalidKey}');
                },
            ],
            'set' => [
                function (SimpleCache $cache) {
                    $cache->set('{invalidKey}', 'value');
                },
            ],
            'delete' => [
                function (SimpleCache $cache) {
                    $cache->delete('{invalidKey}');
                },
            ],
            'getMultiple' => [
                function (SimpleCache $cache) {
                    $items = $cache->getMultiple(['{invalidKey}']);
                    // Without the following line, PHP optimizes the opcodes by
                    // removing the call to getMultiple, which makes the test
                    // fail.
                    self::assertEmpty($items);
                },
            ],
            'setMultiple' => [
                function (SimpleCache $cache) {
                    $cache->setMultiple(['{invalidKey}' => 'value']);
                },
            ],
            'deleteMultiple' => [
                function (SimpleCache $cache) {
                    $cache->deleteMultiple(['{invalidKey}']);
                },
            ],
            'has' => [
                function (SimpleCache $cache) {
                    $cache->has('{invalidKey}');
                },
            ],
        ];
    }
}
