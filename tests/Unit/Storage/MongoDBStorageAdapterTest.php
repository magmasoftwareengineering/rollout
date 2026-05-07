<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Tests\Unit\Storage;

use Codeception\Test\Unit;
use MagmaSoftwareEngineering\Rollout\Storage\MongoDBStorageAdapter;
use MongoDB\Collection;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests against the rewritten ext-mongodb adapter. Mocks the
 * \MongoDB\Collection used at the adapter boundary; integration tests
 * against a running mongod land in tests/Integration when added.
 *
 * @requires extension mongodb
 */
class MongoDBStorageAdapterTest extends Unit
{
    #[Test]
    public function getReturnsValueFieldFromDocument(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects(self::once())
            ->method('findOne')
            ->with(['name' => 'key'], self::anything())
            ->willReturn(['name' => 'key', 'value' => 'serialised-payload']);

        $adapter = new MongoDBStorageAdapter($collection);

        self::assertSame('serialised-payload', $adapter->get('key'));
    }

    #[Test]
    public function getReturnsNullWhenDocumentMissing(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('findOne')->willReturn(null);

        $adapter = new MongoDBStorageAdapter($collection);

        self::assertNull($adapter->get('absent'));
    }

    #[Test]
    public function getReturnsNullWhenValueFieldIsNotAString(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('findOne')->willReturn(['name' => 'key', 'value' => ['not', 'a', 'string']]);

        $adapter = new MongoDBStorageAdapter($collection);

        self::assertNull($adapter->get('key'));
    }

    #[Test]
    public function setIssuesUpsertingUpdate(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects(self::once())
            ->method('updateOne')
            ->with(
                ['name' => 'key'],
                ['$set' => ['value' => 'payload']],
                ['upsert' => true],
            );

        $adapter = new MongoDBStorageAdapter($collection);
        $adapter->set('key', 'payload');
    }

    #[Test]
    public function removeIssuesDeleteOne(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects(self::once())
            ->method('deleteOne')
            ->with(['name' => 'key']);

        $adapter = new MongoDBStorageAdapter($collection);
        $adapter->remove('key');
    }

    #[Test]
    public function getCollectionNameDelegatesToDriver(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('getCollectionName')->willReturn('feature_test');

        $adapter = new MongoDBStorageAdapter($collection);

        self::assertSame('feature_test', $adapter->getCollectionName());
    }
}
