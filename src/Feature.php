<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout;

/**
 * @author Richard Fullmer <richard.fullmer@opensoftdev.com>
 * @author Jeremy Coates <hello@phpcodemonkey.me.uk> (3.0 modernisation)
 */
class Feature implements FeatureInterface
{
    /** @var list<string> */
    private array $groups = [];

    /** @var list<string> */
    private array $users = [];

    private int $percentage = 0;

    private ?string $requestParam = null;

    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(private readonly string $name, ?string $settings = null)
    {
        if ($settings === null || $settings === '') {
            $this->clear();
            return;
        }

        // Format detection: 3.0+ emits JSON (always starts with '{').
        // 2.x emitted pipe-delimited records (first segment is the percentage,
        // i.e. always a digit). Reading both keeps existing storage data
        // loadable across an upgrade.
        if ($settings[0] === '{') {
            $this->loadJsonFormat($settings);
            return;
        }

        $this->loadLegacyPipeFormat($settings);
    }

    private function loadJsonFormat(string $settings): void
    {
        $decoded = json_decode($settings, true);
        if (!is_array($decoded)) {
            $this->clear();
            return;
        }

        $this->percentage = (int) ($decoded['percentage'] ?? 0);
        $this->users = $this->stringListFromMixed($decoded['users'] ?? null);
        $this->groups = $this->stringListFromMixed($decoded['groups'] ?? null);
        $rawRequestParam = $decoded['requestParam'] ?? null;
        $this->requestParam = is_string($rawRequestParam) && $rawRequestParam !== ''
            ? $rawRequestParam
            : null;
        /** @var mixed $rawData */
        $rawData = $decoded['data'] ?? null;
        /** @psalm-suppress MixedAssignment, MixedPropertyTypeCoercion */
        $this->data = is_array($rawData) ? $rawData : [];
    }

    private function loadLegacyPipeFormat(string $settings): void
    {
        $parts = explode('|', $settings);

        if (isset($parts[3]) && $parts[3] !== '') {
            $this->requestParam = $parts[3];
        }

        // Older payloads sometimes omit later fields, so we can't rely on a
        // single list() destructure for the whole array.
        if (isset($parts[4]) && $parts[4] !== '') {
            $decoded = json_decode($parts[4], true);
            /** @psalm-suppress MixedPropertyTypeCoercion */
            $this->data = is_array($decoded) ? $decoded : [];
        }

        $rawPercentage = $parts[0] ?? '0';
        $rawUsers = $parts[1] ?? '';
        $rawGroups = $parts[2] ?? '';
        $this->percentage = (int) $rawPercentage;
        $this->users = $rawUsers !== '' ? explode(',', $rawUsers) : [];
        $this->groups = $rawGroups !== '' ? explode(',', $rawGroups) : [];
    }

    /**
     * Coerce a possibly-mixed value into a list<string>, ignoring entries
     * that aren't string-like.
     *
     * @return list<string>
     */
    private function stringListFromMixed(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        /** @var mixed $entry */
        foreach ($value as $entry) {
            if (is_string($entry)) {
                $out[] = $entry;
            }
        }

        return $out;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setPercentage(int $percentage): void
    {
        $this->percentage = $percentage;
    }

    public function getPercentage(): int
    {
        return $this->percentage;
    }

    public function serialize(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public function addUser(RolloutUserInterface $user): void
    {
        $id = $user->getRolloutIdentifier();
        if (!in_array($id, $this->users, true)) {
            $this->users[] = $id;
        }
    }

    public function removeUser(RolloutUserInterface $user): void
    {
        $key = array_search($user->getRolloutIdentifier(), $this->users, true);
        if ($key !== false) {
            unset($this->users[$key]);
            $this->users = array_values($this->users);
        }
    }

    /**
     * @return list<string>
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    public function addGroup(string $group): void
    {
        if (!in_array($group, $this->groups, true)) {
            $this->groups[] = $group;
        }
    }

    public function removeGroup(string $group): void
    {
        $key = array_search($group, $this->groups, true);
        if ($key !== false) {
            unset($this->groups[$key]);
            $this->groups = array_values($this->groups);
        }
    }

    /**
     * @return list<string>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getRequestParam(): ?string
    {
        return $this->requestParam;
    }

    public function setRequestParam(?string $requestParam): void
    {
        $this->requestParam = $requestParam;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Clear the feature of all configuration.
     */
    public function clear(): void
    {
        $this->groups = [];
        $this->users = [];
        $this->percentage = 0;
        $this->requestParam = null;
        $this->data = [];
    }

    /**
     * @param array<string, mixed> $requestParameters
     */
    public function isActive(RolloutInterface $rollout, ?RolloutUserInterface $user = null, array $requestParameters = []): bool
    {
        if ($user === null) {
            return $this->isParamInRequestParams($requestParameters)
                || $this->percentage === 100
                || $this->isInActiveGroup($rollout);
        }

        return $this->isParamInRequestParams($requestParameters)
            || $this->isUserInPercentage($user)
            || $this->isUserInActiveUsers($user)
            || $this->isInActiveGroup($rollout, $user);
    }

    /**
     * @return array{percentage: int, groups: list<string>, users: list<string>, requestParam: ?string, data: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'percentage' => $this->percentage,
            'groups' => $this->groups,
            'users' => $this->users,
            'requestParam' => $this->requestParam,
            'data' => $this->data,
        ];
    }

    /**
     * @param array<string, mixed> $requestParameters
     */
    private function isParamInRequestParams(array $requestParameters): bool
    {
        if ($this->requestParam === null || $this->requestParam === '') {
            return false;
        }

        $parts = explode('=', $this->requestParam);
        $key = array_shift($parts);
        $value = array_shift($parts);

        if ($key === null || $key === '' || !array_key_exists($key, $requestParameters)) {
            return false;
        }

        if ($value === null || $value === '') {
            return true;
        }

        return (string) $requestParameters[$key] === $value;
    }

    private function isUserInPercentage(RolloutUserInterface $user): bool
    {
        return abs(crc32($user->getRolloutIdentifier()) % 100) < $this->percentage;
    }

    private function isUserInActiveUsers(RolloutUserInterface $user): bool
    {
        return in_array($user->getRolloutIdentifier(), $this->users, true);
    }

    private function isInActiveGroup(RolloutInterface $rollout, ?RolloutUserInterface $user = null): bool
    {
        foreach ($this->groups as $group) {
            if ($rollout->isActiveInGroup($group, $user)) {
                return true;
            }
        }

        return false;
    }
}
