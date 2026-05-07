<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Tests\Unit\Storage;

use Codeception\Test\Unit;
use MagmaSoftwareEngineering\Rollout\Storage\ArrayStorage;
use PHPUnit\Framework\Attributes\Test;

class ArrayStorageTest extends Unit
{
    private ArrayStorage $storage;

    protected function _before(): void
    {
        $this->storage = new ArrayStorage();
    }

    #[Test]
    public function getReturnsNullForUnsetKey(): void
    {
        self::assertNull($this->storage->get('missing'));
    }

    #[Test]
    public function setAndGetRoundTripsString(): void
    {
        $this->storage->set('foo', 'bar');

        self::assertSame('bar', $this->storage->get('foo'));
    }

    #[Test]
    public function setOverwritesExistingValue(): void
    {
        $this->storage->set('foo', 'one');
        $this->storage->set('foo', 'two');

        self::assertSame('two', $this->storage->get('foo'));
    }

    #[Test]
    public function removeMakesKeyUnreachable(): void
    {
        $this->storage->set('foo', 'bar');
        $this->storage->remove('foo');

        self::assertNull($this->storage->get('foo'));
    }

    #[Test]
    public function removingMissingKeyIsANoOp(): void
    {
        $this->storage->remove('never-set');

        self::assertNull($this->storage->get('never-set'));
    }
}
