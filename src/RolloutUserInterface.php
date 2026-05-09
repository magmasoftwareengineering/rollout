<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout;

/**
 * @author Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
interface RolloutUserInterface
{
    public function getRolloutIdentifier(): string;
}
