<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout;

use Closure;

/**
 * Public surface of the rollout engine.
 *
 * Consumers should depend on this interface rather than the concrete
 * {@see Rollout} class, which allows test doubles, decorators, and
 * downstream extensions (e.g. group-definition-aware variants) to be
 * substituted via DI containers.
 */
interface RolloutInterface
{
    public function activate(string $feature): void;

    public function deactivate(string $feature): void;

    public function activateGroup(string $feature, string $group): void;

    public function deactivateGroup(string $feature, string $group): void;

    public function activateUser(string $feature, RolloutUserInterface $user): void;

    public function deactivateUser(string $feature, RolloutUserInterface $user): void;

    public function defineGroup(string $group, Closure $closure): void;

    /**
     * @param array<string, mixed> $requestParameters
     */
    public function isActive(
        string $feature,
        ?RolloutUserInterface $user = null,
        array $requestParameters = [],
    ): bool;

    public function activatePercentage(string $feature, int $percentage): void;

    public function deactivatePercentage(string $feature): void;

    public function activateRequestParam(string $feature, string $requestParam): void;

    public function deactivateRequestParam(string $feature): void;

    public function isActiveInGroup(string $group, ?RolloutUserInterface $user = null): bool;

    public function get(string $feature): FeatureInterface;

    public function remove(string $feature): void;

    /**
     * @param array<string, mixed> $data
     */
    public function setFeatureData(string $feature, array $data): void;

    public function clearFeatureData(string $feature): void;

    /**
     * @return list<string>
     */
    public function features(): array;
}
