<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Tests\Unit;

use Codeception\Test\Unit;
use MagmaSoftwareEngineering\Rollout\Feature;
use MagmaSoftwareEngineering\Rollout\LegacyFormatConverter;
use MagmaSoftwareEngineering\Rollout\Storage\ArrayStorage;
use PHPUnit\Framework\Attributes\Test;

/**
 * @author Jeremy Coates <hello@phpcodemonkey.me.uk>
 */
class LegacyFormatConverterTest extends Unit
{
    #[Test]
    public function serializeLegacy_roundTripsThroughTheLegacyReader(): void
    {
        $feature = new Feature('chat', '{"percentage":72,"users":["4","12"],"groups":["fivesonly"],"requestParam":"ff_chat","data":{"note":"x"}}');

        $legacy = $feature->serializeLegacy();
        self::assertSame('72|4,12|fivesonly|ff_chat|{"note":"x"}', $legacy);

        $reread = new Feature('chat', $legacy);
        self::assertSame(72, $reread->getPercentage());
        self::assertSame(['4', '12'], $reread->getUsers());
        self::assertSame(['fivesonly'], $reread->getGroups());
        self::assertSame('ff_chat', $reread->getRequestParam());
        self::assertSame(['note' => 'x'], $reread->getData());
    }

    #[Test]
    public function serializeLegacy_emptyFeatureMatchesTwoPointXShape(): void
    {
        $feature = new Feature('bare', '{"percentage":100,"groups":[],"users":[],"requestParam":null,"data":[]}');

        self::assertSame('100||||[]', $feature->serializeLegacy());
    }

    #[Test]
    public function downgrade_rewritesJsonRecordsAndCountsThem(): void
    {
        $storage = new ArrayStorage();
        $storage->set('feature:__features__', 'chat,billing,legacy');
        $storage->set('feature:chat', '{"percentage":100,"groups":[],"users":[],"requestParam":null,"data":[]}');
        $storage->set('feature:billing', '{"percentage":50,"groups":["staff"],"users":["9"],"requestParam":null,"data":[]}');
        $storage->set('feature:legacy', '25||||[]');

        $converted = (new LegacyFormatConverter($storage))->downgrade();

        self::assertSame(2, $converted);
        self::assertSame('100||||[]', $storage->get('feature:chat'));
        self::assertSame('50|9|staff||[]', $storage->get('feature:billing'));
        self::assertSame('25||||[]', $storage->get('feature:legacy'));
    }

    #[Test]
    public function downgrade_isIdempotent(): void
    {
        $storage = new ArrayStorage();
        $storage->set('feature:__features__', 'chat');
        $storage->set('feature:chat', '{"percentage":100,"groups":[],"users":[],"requestParam":null,"data":[]}');

        $converter = new LegacyFormatConverter($storage);
        self::assertSame(1, $converter->downgrade());
        self::assertSame(0, $converter->downgrade());
    }

    #[Test]
    public function downgrade_withNoFeatureListDoesNothing(): void
    {
        $storage = new ArrayStorage();

        self::assertSame(0, (new LegacyFormatConverter($storage))->downgrade());
    }
}
