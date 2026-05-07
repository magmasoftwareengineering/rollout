<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Storage;

/**
 * Storage adapter using MongoDB.
 *
 * NOTE: This adapter currently retains the legacy ext-mongo / wire-shape
 * implementation from upstream opensoft/rollout. A rewrite for ext-mongodb
 * (the modern replacement) is scheduled for a follow-up commit in this 3.0
 * series. Until then, this adapter is unlikely to function on PHP 8.3+.
 *
 * @author James Hrisho <@securingsincity>
 */
class MongoDBStorageAdapter implements StorageInterface
{
    private readonly string $collection;

    public function __construct(
        private readonly object $mongo,
        ?string $collection = null,
    ) {
        $this->collection = $collection ?? 'rollout_feature';
    }

    public function getCollectionName(): string
    {
        return $this->collection;
    }

    public function get(string $key): ?string
    {
        $collection = $this->getCollectionName();
        /** @psalm-suppress MixedMethodCall, MixedAssignment, MixedPropertyFetch */
        $result = $this->mongo->$collection->findOne(['name' => $key]);

        if (!$result) {
            return null;
        }

        /** @psalm-suppress MixedArrayAccess */
        return (string) $result['value'];
    }

    public function set(string $key, string $value): void
    {
        $collection = $this->getCollectionName();
        /** @psalm-suppress MixedMethodCall, MixedPropertyFetch */
        $this->mongo->$collection->update(
            ['name' => $key],
            ['$set' => ['value' => $value]],
            ['upsert' => true],
        );
    }

    public function remove(string $key): void
    {
        $collection = $this->getCollectionName();
        /** @psalm-suppress MixedMethodCall, MixedPropertyFetch */
        $this->mongo->$collection->remove(['name' => $key]);
    }
}
