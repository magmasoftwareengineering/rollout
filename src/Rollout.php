<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout;

use Closure;
use MagmaSoftwareEngineering\Rollout\Storage\StorageInterface;

/**
 * @author Richard Fullmer <richard.fullmer@opensoftdev.com>
 * @author Jeremy Coates <hello@phpcodemonkey.me.uk> (3.0 modernisation)
 */
class Rollout
{
    /** @var array<string, Closure> */
    private array $groups;

    public function __construct(private readonly StorageInterface $storage)
    {
        $this->groups = [
            'all' => static fn (?RolloutUserInterface $user): bool => $user !== null,
        ];
    }

    public function activate(string $feature): void
    {
        $f = $this->get($feature);
        $f->setPercentage(100);
        $this->save($f);
    }

    public function deactivate(string $feature): void
    {
        $f = $this->get($feature);
        $f->clear();
        $this->save($f);
    }

    public function activateGroup(string $feature, string $group): void
    {
        $f = $this->get($feature);
        $f->addGroup($group);
        $this->save($f);
    }

    public function deactivateGroup(string $feature, string $group): void
    {
        $f = $this->get($feature);
        $f->removeGroup($group);
        $this->save($f);
    }

    public function activateUser(string $feature, RolloutUserInterface $user): void
    {
        $f = $this->get($feature);
        $f->addUser($user);
        $this->save($f);
    }

    public function deactivateUser(string $feature, RolloutUserInterface $user): void
    {
        $f = $this->get($feature);
        $f->removeUser($user);
        $this->save($f);
    }

    public function defineGroup(string $group, Closure $closure): void
    {
        $this->groups[$group] = $closure;
    }

    /**
     * @param array<string, mixed> $requestParameters
     */
    public function isActive(string $feature, ?RolloutUserInterface $user = null, array $requestParameters = []): bool
    {
        return $this->get($feature)->isActive($this, $user, $requestParameters);
    }

    public function activatePercentage(string $feature, int $percentage): void
    {
        $f = $this->get($feature);
        $f->setPercentage($percentage);
        $this->save($f);
    }

    public function deactivatePercentage(string $feature): void
    {
        $f = $this->get($feature);
        $f->setPercentage(0);
        $this->save($f);
    }

    public function activateRequestParam(string $feature, string $requestParam): void
    {
        $f = $this->get($feature);
        $f->setRequestParam($requestParam);
        $this->save($f);
    }

    public function deactivateRequestParam(string $feature): void
    {
        $f = $this->get($feature);
        $f->setRequestParam('');
        $this->save($f);
    }

    public function isActiveInGroup(string $group, ?RolloutUserInterface $user = null): bool
    {
        if (!isset($this->groups[$group])) {
            return false;
        }

        return (bool) ($this->groups[$group])($user);
    }

    public function get(string $feature): Feature
    {
        $settings = $this->storage->get($this->key($feature));

        if (!empty($settings)) {
            return new Feature($feature, $settings);
        }

        $f = new Feature($feature);
        $this->save($f);

        return $f;
    }

    /**
     * Remove a feature definition from rollout.
     */
    public function remove(string $feature): void
    {
        $this->storage->remove($this->key($feature));

        $features = $this->features();
        if (in_array($feature, $features, true)) {
            $features = array_diff($features, [$feature]);
        }
        $this->storage->set($this->featuresKey(), implode(',', $features));
    }

    /**
     * Update feature-specific data.
     *
     * @example $rollout->setFeatureData('chat', [
     *   'description'  => 'foo',
     *   'release_date' => 'bar',
     *   'whatever'     => 'baz',
     * ]);
     *
     * @param array<string, mixed> $data
     */
    public function setFeatureData(string $feature, array $data): void
    {
        $f = $this->get($feature);
        $f->setData(array_merge($f->getData(), $data));
        $this->save($f);
    }

    /**
     * Clear all feature data.
     */
    public function clearFeatureData(string $feature): void
    {
        $f = $this->get($feature);
        $f->setData([]);
        $this->save($f);
    }

    /**
     * @return list<string>
     */
    public function features(): array
    {
        $content = $this->storage->get($this->featuresKey());

        if (!empty($content)) {
            return explode(',', $content);
        }

        return [];
    }

    private function key(string $name): string
    {
        return 'feature:' . $name;
    }

    private function featuresKey(): string
    {
        return 'feature:__features__';
    }

    protected function save(Feature $feature): void
    {
        $name = $feature->getName();
        $this->storage->set($this->key($name), $feature->serialize());

        $features = $this->features();
        if (!in_array($name, $features, true)) {
            $features[] = $name;
        }
        $this->storage->set($this->featuresKey(), implode(',', $features));
    }
}
