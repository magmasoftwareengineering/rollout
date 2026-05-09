<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Tests\Helper;

use MagmaSoftwareEngineering\Rollout\RolloutUserInterface;

/**
 * Test fixture: a minimal RolloutUserInterface implementation that wraps a
 * single identifier. Used by the Rollout unit tests to construct user
 * subjects for percentage / explicit-user / group activation scenarios.
 */
final readonly class RolloutUser implements RolloutUserInterface
{
    public function __construct(private string|int $id) {}

    public function getRolloutIdentifier(): string
    {
        return (string) $this->id;
    }
}
