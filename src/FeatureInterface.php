<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout;

/**
 * Public surface of a feature flag definition. Most callers interact with
 * features through {@see RolloutInterface} rather than holding instances
 * directly; this interface exists so storage adapters, decorators, and
 * test doubles can substitute alternative implementations.
 */
interface FeatureInterface
{
    public function getName(): string;

    public function setPercentage(int $percentage): void;

    public function getPercentage(): int;

    public function serialize(): string;

    public function addUser(RolloutUserInterface $user): void;

    public function removeUser(RolloutUserInterface $user): void;

    /**
     * @return list<string>
     */
    public function getUsers(): array;

    public function addGroup(string $group): void;

    public function removeGroup(string $group): void;

    /**
     * @return list<string>
     */
    public function getGroups(): array;

    public function getRequestParam(): ?string;

    public function setRequestParam(?string $requestParam): void;

    /**
     * @return array<string, mixed>
     */
    public function getData(): array;

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void;

    /**
     * Clear the feature of all configuration.
     */
    public function clear(): void;

    /**
     * @param array<string, mixed> $requestParameters
     */
    public function isActive(
        RolloutInterface $rollout,
        ?RolloutUserInterface $user = null,
        array $requestParameters = [],
    ): bool;

    /**
     * @return array{percentage: int, groups: list<string>, users: list<string>, requestParam: ?string, data: array<string, mixed>}
     */
    public function toArray(): array;
}
