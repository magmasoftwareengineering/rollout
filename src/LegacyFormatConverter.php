<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout;

use MagmaSoftwareEngineering\Rollout\Storage\StorageInterface;

/**
 * Rewrites every stored feature from the 3.x JSON settings format back to
 * the 2.x pipe-delimited format.
 *
 * The 3.x Feature reader accepts both formats, but 2.x (and the original
 * opensoft/rollout) only parses pipe records — an application rollback to
 * the older engine against storage the 3.x engine has written to will fail
 * with parse errors until the settings are downgraded. Run this once against
 * the shared storage before (or immediately after) reverting the
 * application:
 *
 *     $count = (new LegacyFormatConverter($storage))->downgrade();
 *
 * Idempotent: pipe-format records round-trip unchanged.
 */
final class LegacyFormatConverter
{
    public function __construct(private readonly StorageInterface $storage)
    {
    }

    /**
     * Downgrade every registered feature's stored settings to the legacy
     * pipe format. Returns the number of features rewritten.
     */
    public function downgrade(): int
    {
        $names = $this->featureNames();

        $converted = 0;
        foreach ($names as $name) {
            $key = 'feature:' . $name;
            $settings = $this->storage->get($key);
            if ($settings === null || $settings === '') {
                continue;
            }

            $legacy = (new Feature($name, $settings))->serializeLegacy();
            if ($legacy !== $settings) {
                $this->storage->set($key, $legacy);
                $converted++;
            }
        }

        return $converted;
    }

    /**
     * @return list<string>
     */
    private function featureNames(): array
    {
        $content = $this->storage->get('feature:__features__');
        if ($content === null || $content === '') {
            return [];
        }

        return explode(',', $content);
    }
}
