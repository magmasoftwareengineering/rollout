<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Tests\Unit;

use Codeception\Test\Unit;
use MagmaSoftwareEngineering\Rollout\Feature;
use PHPUnit\Framework\Attributes\Test;

/**
 * @author Filip Golonka <filipgolonka@gmail.com> (original 2.x tests)
 * @author Jeremy Coates <hello@phpcodemonkey.me.uk> (3.0 JSON-format tests)
 */
class FeatureTest extends Unit
{
    // -- Legacy pipe-format reader (data written by opensoft/rollout 2.x) --

    #[Test]
    public function legacyFormat_percentageOnly(): void
    {
        $feature = new Feature('chat', '100|4,12|fivesonly');

        self::assertSame(100, $feature->getPercentage());
        self::assertSame(['4', '12'], $feature->getUsers());
        self::assertSame(['fivesonly'], $feature->getGroups());
        self::assertNull($feature->getRequestParam());
        self::assertSame([], $feature->getData());
    }

    #[Test]
    public function legacyFormat_withRequestParam(): void
    {
        $feature = new Feature('chat', '100|4,12|fivesonly|FF_facebookIntegration=1');

        self::assertSame(100, $feature->getPercentage());
        self::assertSame(['4', '12'], $feature->getUsers());
        self::assertSame(['fivesonly'], $feature->getGroups());
        self::assertSame('FF_facebookIntegration=1', $feature->getRequestParam());
    }

    #[Test]
    public function legacyFormat_withData(): void
    {
        $feature = new Feature('chat', '100|4,12|fivesonly|FF_facebookIntegration=1|{"description":"foo","release_date":"bar"}');

        self::assertSame(100, $feature->getPercentage());
        self::assertSame(['4', '12'], $feature->getUsers());
        self::assertSame(['fivesonly'], $feature->getGroups());
        self::assertSame('FF_facebookIntegration=1', $feature->getRequestParam());
        self::assertSame(
            ['description' => 'foo', 'release_date' => 'bar'],
            $feature->getData(),
        );
    }

    // -- 3.0 JSON wire format --

    #[Test]
    public function newFormat_serialiseEmitsJson(): void
    {
        $feature = new Feature('chat');
        $feature->setPercentage(50);
        $feature->addGroup('admins');
        $feature->setData(['description' => 'foo']);

        $serialised = $feature->serialize();

        self::assertJson($serialised);
        $decoded = json_decode($serialised, true);
        self::assertSame(50, $decoded['percentage']);
        self::assertSame(['admins'], $decoded['groups']);
        self::assertSame(['description' => 'foo'], $decoded['data']);
    }

    #[Test]
    public function newFormat_roundTripPreservesAllFields(): void
    {
        $original = new Feature('chat');
        $original->setPercentage(25);
        $original->addGroup('admins');
        $original->addGroup('beta');
        $original->setRequestParam('feature_flag=on');
        $original->setData(['note' => 'release-2026']);

        $reloaded = new Feature('chat', $original->serialize());

        self::assertSame(25, $reloaded->getPercentage());
        self::assertSame(['admins', 'beta'], $reloaded->getGroups());
        self::assertSame('feature_flag=on', $reloaded->getRequestParam());
        self::assertSame(['note' => 'release-2026'], $reloaded->getData());
    }

    #[Test]
    public function newFormat_legacyDataReserialisedAsJson(): void
    {
        $loaded = new Feature('chat', '50|user1|admins||{}');

        self::assertJson($loaded->serialize());
    }

    // -- Edge cases --

    #[Test]
    public function emptyConstructor_yieldsClearedFeature(): void
    {
        $feature = new Feature('empty');

        self::assertSame(0, $feature->getPercentage());
        self::assertSame([], $feature->getUsers());
        self::assertSame([], $feature->getGroups());
        self::assertNull($feature->getRequestParam());
        self::assertSame([], $feature->getData());
    }

    #[Test]
    public function garbageInput_degradesToEmptyFeature(): void
    {
        $feature = new Feature('garbage', 'totally-not-valid-data');

        self::assertSame(0, $feature->getPercentage());
        self::assertSame([], $feature->getUsers());
        self::assertSame([], $feature->getGroups());
    }

    #[Test]
    public function malformedJson_degradesToEmptyFeature(): void
    {
        $feature = new Feature('garbage', '{not valid json}');

        self::assertSame(0, $feature->getPercentage());
    }
}
