<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Storage;

use MongoDB\Collection;

/**
 * Storage adapter using MongoDB via the modern mongodb/mongodb library
 * and ext-mongodb.
 *
 * Construct with a fully-resolved {@see \MongoDB\Collection}; the adapter
 * stores each feature as a single document keyed by `name`:
 *
 *     { name: "feature:chat", value: "<serialised payload>" }
 *
 * Suggested setup:
 *
 *     $client = new \MongoDB\Client('mongodb://localhost:27017');
 *     $collection = $client->selectCollection('app', 'rollout_feature');
 *     $storage = new MongoDBStorageAdapter($collection);
 *
 * @author James Hrisho <@securingsincity> (original ext-mongo implementation)
 * @author Jeremy Coates <hello@phpcodemonkey.me.uk> (3.0 ext-mongodb rewrite)
 */
class MongoDBStorageAdapter implements StorageInterface
{
    public function __construct(private readonly Collection $collection) {}

    public function getCollectionName(): string
    {
        return $this->collection->getCollectionName();
    }

    #[\Override]
    public function get(string $key): ?string
    {
        /** @var array{value?: mixed}|null $document */
        $document = $this->collection->findOne(
            ['name' => $key],
            ['typeMap' => ['root' => 'array', 'document' => 'array']],
        );

        if ($document === null) {
            return null;
        }

        $value = $document['value'] ?? null;

        return is_string($value) ? $value : null;
    }

    #[\Override]
    public function set(string $key, string $value): void
    {
        $this->collection->updateOne(
            ['name' => $key],
            ['$set' => ['value' => $value]],
            ['upsert' => true],
        );
    }

    #[\Override]
    public function remove(string $key): void
    {
        $this->collection->deleteOne(['name' => $key]);
    }
}
