<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Tests\Unit;

use Codeception\Test\Unit;
use MagmaSoftwareEngineering\Rollout\Rollout;
use MagmaSoftwareEngineering\Rollout\RolloutUserInterface;
use MagmaSoftwareEngineering\Rollout\Storage\ArrayStorage;
use MagmaSoftwareEngineering\Rollout\Tests\Helper\RolloutUser;
use PHPUnit\Framework\Attributes\Test;

/**
 * @author Richard Fullmer <richard.fullmer@opensoftdev.com> (original tests)
 * @author Jeremy Coates <hello@phpcodemonkey.me.uk> (3.0 modernisation)
 */
class RolloutTest extends Unit
{
    private Rollout $rollout;

    protected function _before(): void
    {
        $this->rollout = new Rollout(new ArrayStorage());
    }

    #[Test]
    public function activeForBlockGroup(): void
    {
        $this->rollout->defineGroup(
            'fivesonly',
            static fn (?RolloutUserInterface $user): bool => $user?->getRolloutIdentifier() === '5',
        );
        $this->rollout->activateGroup('chat', 'fivesonly');

        self::assertTrue($this->rollout->isActive('chat', new RolloutUser(5)));
        self::assertFalse($this->rollout->isActive('chat', new RolloutUser(1)));

        // Group is in storage but not defined → still inactive
        $this->rollout->activateGroup('chat', 'fake');
        self::assertFalse($this->rollout->isActive('chat', new RolloutUser(1)));
    }

    #[Test]
    public function defaultAllGroup(): void
    {
        $this->rollout->activateGroup('chat', 'all');

        self::assertTrue($this->rollout->isActive('chat', new RolloutUser(0)));
    }

    #[Test]
    public function deactivatingAGroup(): void
    {
        $this->rollout->defineGroup(
            'fivesonly',
            static fn (?RolloutUserInterface $user): bool => $user?->getRolloutIdentifier() === '5',
        );
        $this->rollout->activateGroup('chat', 'all');
        $this->rollout->activateGroup('chat', 'some');
        $this->rollout->activateGroup('chat', 'fivesonly');
        $this->rollout->deactivateGroup('chat', 'all');
        $this->rollout->deactivateGroup('chat', 'some');

        self::assertFalse($this->rollout->isActive('chat', new RolloutUser(10)));
        self::assertContains('fivesonly', $this->rollout->get('chat')->getGroups());
        self::assertCount(1, $this->rollout->get('chat')->getGroups());
    }

    #[Test]
    public function deactivatingAFeatureCompletely(): void
    {
        $this->rollout->defineGroup(
            'fivesonly',
            static fn (?RolloutUserInterface $user): bool => $user?->getRolloutIdentifier() === '5',
        );
        $this->rollout->activateGroup('chat', 'all');
        $this->rollout->activateGroup('chat', 'fivesonly');
        $this->rollout->activateUser('chat', new RolloutUser(51));
        $this->rollout->activatePercentage('chat', 100);
        $this->rollout->activateRequestParam('chat', 'FF_facebookIntegration=1');
        $this->rollout->activate('chat');
        $this->rollout->deactivate('chat');

        self::assertFalse($this->rollout->isActive('chat', new RolloutUser(0)));
        self::assertFalse($this->rollout->isActive('chat', new RolloutUser(51)));
        self::assertFalse($this->rollout->isActive('chat', new RolloutUser(24)));
        self::assertFalse($this->rollout->isActive('chat', null, ['FF_facebookIntegration' => '1']));
        self::assertFalse($this->rollout->isActive('chat'));
    }

    #[Test]
    public function activatingASpecificUser(): void
    {
        $this->rollout->activateUser('chat', new RolloutUser(42));

        self::assertTrue($this->rollout->isActive('chat', new RolloutUser(42)));
        self::assertFalse($this->rollout->isActive('chat', new RolloutUser(24)));
    }

    #[Test]
    public function activatingASpecificUserWithStringId(): void
    {
        $this->rollout->activateUser('chat', new RolloutUser('user-72'));

        self::assertTrue($this->rollout->isActive('chat', new RolloutUser('user-72')));
        self::assertFalse($this->rollout->isActive('chat', new RolloutUser('user-12')));
    }

    #[Test]
    public function deactivatingASpecificUser(): void
    {
        $this->rollout->activateUser('chat', new RolloutUser(42));
        $this->rollout->activateUser('chat', new RolloutUser(4242));
        $this->rollout->activateUser('chat', new RolloutUser(24));
        $this->rollout->deactivateUser('chat', new RolloutUser(42));
        $this->rollout->deactivateUser('chat', new RolloutUser('4242'));

        self::assertFalse($this->rollout->isActive('chat', new RolloutUser(42)));

        $users = $this->rollout->get('chat')->getUsers();
        self::assertCount(1, $users);
        self::assertSame('24', $users[0]);
    }

    #[Test]
    public function activatingAFeatureGlobally(): void
    {
        $this->rollout->activate('chat');

        self::assertTrue($this->rollout->isActive('chat'));
    }

    /**
     * Percentage-rollout tests are statistical: the CRC32-based hashing
     * gives deterministic but spread-out activation. Tolerance windows
     * inherited from the upstream tests.
     */
    #[Test]
    public function activatingAFeatureForPercentageOfUsers_20pctOf120(): void
    {
        $this->rollout->activatePercentage('chat', 20);

        $count = 0;
        foreach (range(1, 120) as $id) {
            if ($this->rollout->isActive('chat', new RolloutUser($id))) {
                ++$count;
            }
        }

        self::assertGreaterThanOrEqual(19, $count);
        self::assertLessThanOrEqual(21, $count);
    }

    #[Test]
    public function activatingAFeatureForPercentageOfUsers_20pctOf200(): void
    {
        $this->rollout->activatePercentage('chat', 20);

        $count = 0;
        foreach (range(1, 200) as $id) {
            if ($this->rollout->isActive('chat', new RolloutUser($id))) {
                ++$count;
            }
        }

        self::assertGreaterThanOrEqual(35, $count);
        self::assertLessThanOrEqual(45, $count);
    }

    #[Test]
    public function activatingAFeatureForPercentageOfUsers_5pctOf100(): void
    {
        $this->rollout->activatePercentage('chat', 5);

        $count = 0;
        foreach (range(1, 100) as $id) {
            if ($this->rollout->isActive('chat', new RolloutUser($id))) {
                ++$count;
            }
        }

        self::assertGreaterThanOrEqual(3, $count);
        self::assertLessThanOrEqual(7, $count);
    }

    #[Test]
    public function activatingAFeatureForAGroupAsAString(): void
    {
        $this->rollout->defineGroup(
            'admins',
            static fn (?RolloutUserInterface $user): bool => $user?->getRolloutIdentifier() === '5',
        );
        $this->rollout->activateGroup('chat', 'admins');

        self::assertTrue($this->rollout->isActive('chat', new RolloutUser(5)));
        self::assertFalse($this->rollout->isActive('chat', new RolloutUser(1)));
    }

    #[Test]
    public function deactivatingThePercentageOfUsers(): void
    {
        $this->rollout->activatePercentage('chat', 100);
        $this->rollout->deactivatePercentage('chat');

        self::assertFalse($this->rollout->isActive('chat', new RolloutUser(24)));
    }

    #[Test]
    public function activatingRequestParam(): void
    {
        $this->rollout->activateRequestParam('chat', 'FF_facebookIntegration=1');

        self::assertTrue($this->rollout->isActive('chat', null, ['FF_facebookIntegration' => '1']));
        self::assertFalse($this->rollout->isActive('chat', null, ['FF_anotherFeature' => '1']));
    }

    #[Test]
    public function deactivatingRequestParam(): void
    {
        $this->rollout->activateRequestParam('chat', 'FF_facebookIntegration=1');
        $this->rollout->deactivateRequestParam('chat');

        self::assertFalse($this->rollout->isActive('chat', null, ['FF_facebookIntegration' => '1']));
        self::assertFalse($this->rollout->isActive('chat', null, ['FF_anotherFeature' => '1']));
    }

    #[Test]
    public function deactivatingTheFeatureGlobally(): void
    {
        $this->rollout->activate('chat');
        $this->rollout->deactivate('chat');

        self::assertFalse($this->rollout->isActive('chat'));
    }

    #[Test]
    public function keepsAListOfFeatures(): void
    {
        $this->rollout->activate('chat');
        self::assertContains('chat', $this->rollout->features());

        // No duplicates on repeated activation
        $this->rollout->activate('chat');
        $this->rollout->activate('chat');
        self::assertCount(1, $this->rollout->features());
    }

    #[Test]
    public function get(): void
    {
        $this->rollout->activatePercentage('chat', 10);
        $this->rollout->activateGroup('chat', 'caretakers');
        $this->rollout->activateGroup('chat', 'greeters');
        $this->rollout->activate('signup');
        $this->rollout->activateUser('chat', new RolloutUser(42));
        $this->rollout->activateRequestParam('chat', 'FF_facebookIntegration=1');
        $this->rollout->setFeatureData('chat', [
            'description' => 'foo',
            'release_date' => 'bar',
            'whatever' => 'baz',
        ]);

        $feature = $this->rollout->get('chat');
        self::assertContains('caretakers', $feature->getGroups());
        self::assertContains('greeters', $feature->getGroups());
        self::assertSame(10, $feature->getPercentage());
        self::assertContains('42', $feature->getUsers());
        self::assertSame(
            [
                'percentage' => 10,
                'groups' => ['caretakers', 'greeters'],
                'users' => ['42'],
                'requestParam' => 'FF_facebookIntegration=1',
                'data' => [
                    'description' => 'foo',
                    'release_date' => 'bar',
                    'whatever' => 'baz',
                ],
            ],
            $feature->toArray(),
        );

        $signup = $this->rollout->get('signup');
        self::assertSame([], $signup->getGroups());
        self::assertSame([], $signup->getUsers());
        self::assertSame(100, $signup->getPercentage());
        self::assertNull($signup->getRequestParam());
        self::assertSame([], $signup->getData());
    }

    #[Test]
    public function remove(): void
    {
        $this->rollout->activate('signup');
        self::assertSame('signup', $this->rollout->get('signup')->getName());

        $this->rollout->remove('signup');
        self::assertNotContains('signup', $this->rollout->features());
    }

    #[Test]
    public function clearFeatureData(): void
    {
        $this->rollout->activate('signup');
        self::assertSame('signup', $this->rollout->get('signup')->getName());

        $this->rollout->setFeatureData('signup', [
            'description' => 'foo',
            'release_date' => 'bar',
            'whatever' => 'baz',
        ]);

        self::assertSame(
            [
                'description' => 'foo',
                'release_date' => 'bar',
                'whatever' => 'baz',
            ],
            $this->rollout->get('signup')->getData(),
        );

        $this->rollout->clearFeatureData('signup');

        self::assertSame([], $this->rollout->get('signup')->getData());
    }
}
