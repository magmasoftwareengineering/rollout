<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Tests\Unit\Storage;

use Codeception\Test\Unit;
use MagmaSoftwareEngineering\Rollout\Storage\RedisStorageAdapter;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

/**
 * Tests use anonymous-class doubles rather than PHPUnit mocks because both
 * \Redis (ext-redis) and \Predis\Client are typed as `object` on the
 * adapter constructor — we want to exercise that any object with the right
 * shape works, not assert against a specific upstream class.
 */
class RedisStorageAdapterTest extends Unit
{
    #[Test]
    public function getReturnsDecodedString(): void
    {
        $redis = $this->fakeRedis(hgetReturns: json_encode('success'));
        $adapter = new RedisStorageAdapter($redis);

        self::assertSame('success', $adapter->get('key'));
    }

    #[Test]
    public function getUsesCustomGroupWhenProvided(): void
    {
        $redis = $this->fakeRedis(hgetReturns: json_encode('success'));
        $adapter = new RedisStorageAdapter($redis, 'rollout_test');

        $adapter->get('key');

        self::assertSame('rollout_test', $redis->lastGroupForHget);
    }

    #[Test]
    public function getReturnsNullWhenEmpty(): void
    {
        $redis = $this->fakeRedis(hgetReturns: '');
        $adapter = new RedisStorageAdapter($redis);

        self::assertNull($adapter->get('key'));
    }

    #[Test]
    public function getReturnsNullOnInvalidJson(): void
    {
        $redis = $this->fakeRedis(hgetReturns: 'not json{{{');
        $adapter = new RedisStorageAdapter($redis);

        self::assertNull($adapter->get('key'));
    }

    #[Test]
    public function getReturnsNullWhenStoredValueIsNotAString(): void
    {
        // JSON-encoded array, not a string — adapter contract is ?string
        $redis = $this->fakeRedis(hgetReturns: json_encode(['some' => 'object']));
        $adapter = new RedisStorageAdapter($redis);

        self::assertNull($adapter->get('key'));
    }

    #[Test]
    public function setJsonEncodesValue(): void
    {
        $redis = $this->fakeRedis();
        $adapter = new RedisStorageAdapter($redis);

        $adapter->set('key', 'value');

        self::assertSame(RedisStorageAdapter::DEFAULT_GROUP, $redis->lastGroupForHset);
        self::assertSame('key', $redis->lastFieldForHset);
        self::assertSame(json_encode('value'), $redis->lastValueForHset);
    }

    #[Test]
    public function setUsesCustomGroupWhenProvided(): void
    {
        $redis = $this->fakeRedis();
        $adapter = new RedisStorageAdapter($redis, 'rollout_test');

        $adapter->set('key', 'value');

        self::assertSame('rollout_test', $redis->lastGroupForHset);
    }

    #[Test]
    public function removeIssuesHdel(): void
    {
        $redis = $this->fakeRedis();
        $adapter = new RedisStorageAdapter($redis);

        $adapter->remove('key');

        self::assertSame(RedisStorageAdapter::DEFAULT_GROUP, $redis->lastGroupForHdel);
        self::assertSame('key', $redis->lastFieldForHdel);
    }

    /**
     * Anonymous-class fake matching the duck-typed surface used by the
     * adapter (hget/hset/hdel). The properties capture the most recent
     * call so tests can assert on dispatch shape.
     */
    private function fakeRedis(string|false $hgetReturns = false): object
    {
        return new class ($hgetReturns) extends stdClass {
            public ?string $lastGroupForHget = null;
            public ?string $lastGroupForHset = null;
            public ?string $lastFieldForHset = null;
            public string|false $lastValueForHset = false;
            public ?string $lastGroupForHdel = null;
            public ?string $lastFieldForHdel = null;

            public function __construct(private readonly string|false $hgetReturns) {}

            public function hget(string $group, string $field): string|false
            {
                $this->lastGroupForHget = $group;
                return $this->hgetReturns;
            }

            public function hset(string $group, string $field, string $value): int
            {
                $this->lastGroupForHset = $group;
                $this->lastFieldForHset = $field;
                $this->lastValueForHset = $value;
                return 1;
            }

            public function hdel(string $group, string $field): int
            {
                $this->lastGroupForHdel = $group;
                $this->lastFieldForHdel = $field;
                return 1;
            }
        };
    }
}
