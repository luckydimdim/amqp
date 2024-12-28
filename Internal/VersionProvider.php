<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal;

use Composer\InstalledVersions;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class VersionProvider
{
    /** @var non-empty-string */
    private const DEFAULT_VERSION = 'dev';

    /** @var non-empty-string */
    private const PACKAGE_NAME = 'typhoon/amqp091';

    /** @var ?non-empty-string */
    private static ?string $version = null;

    /**
     * @return non-empty-string
     */
    public static function provide(): string
    {
        return self::$version ??= (static function (): string {
            $version = self::DEFAULT_VERSION;
            if (InstalledVersions::isInstalled(self::PACKAGE_NAME) && ($prettyVersion = InstalledVersions::getPrettyVersion(self::PACKAGE_NAME)) !== null) {
                $version = $prettyVersion ?: self::DEFAULT_VERSION;
            }

            return $version;
        })();
    }
}
