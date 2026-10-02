<?php

declare(strict_types=1);

namespace App\Doctrine\Migrations;

use App\Entity\Attributes\AfterStableRelease;
use App\Entity\Attributes\StableMigration;
use Doctrine\Migrations\Version\Comparator;
use Doctrine\Migrations\Version\Version;
use LogicException;
use ReflectionClass;

/**
 * Sorts migrations alphabetically, except for App migrations marked with #[AfterStableRelease],
 * which are sorted directly after the #[StableMigration] marker of the given stable version.
 */
final class StableReleaseComparator implements Comparator
{
    public const string APP_MIGRATIONS_NAMESPACE = 'App\\Entity\\Migration\\';

    /** @var array<string, string> */
    private array $sortKeys = [];

    /** @var array<string, true> */
    private array $resolving = [];

    /** @var ?array<string, class-string> */
    private ?array $stableMigrations = null;

    public function __construct(
        private readonly string $migrationsDirectory
    ) {
    }

    public function compare(Version $a, Version $b): int
    {
        return strcmp(
            $this->getSortKey((string) $a),
            $this->getSortKey((string) $b)
        );
    }

    /**
     * @return class-string|null The migration marked as the last one of the given stable version.
     */
    public function getStableMigration(string $version): ?string
    {
        if ($this->stableMigrations === null) {
            $this->stableMigrations = [];

            $files = glob($this->migrationsDirectory . '/Version*.php') ?: [];
            rsort($files, SORT_STRING);

            foreach ($files as $file) {
                $className = self::APP_MIGRATIONS_NAMESPACE . basename($file, '.php');
                if (!class_exists($className)) {
                    continue;
                }

                foreach (new ReflectionClass($className)->getAttributes(StableMigration::class) as $attribute) {
                    $this->stableMigrations[$attribute->newInstance()->version] ??= $className;
                }
            }
        }

        return $this->stableMigrations[$version] ?? null;
    }

    private function getSortKey(string $version): string
    {
        return $this->sortKeys[$version] ??= $this->buildSortKey($version);
    }

    private function buildSortKey(string $version): string
    {
        if (
            !str_starts_with($version, self::APP_MIGRATIONS_NAMESPACE)
            || !class_exists($version)
        ) {
            return $version;
        }

        $afterRelease = new ReflectionClass($version)->getAttributes(AfterStableRelease::class)[0] ?? null;
        if ($afterRelease === null) {
            return $version;
        }

        $releaseVersion = $afterRelease->newInstance()->version;

        $stableMigration = $this->getStableMigration($releaseVersion);
        if ($stableMigration === null) {
            throw new LogicException(
                sprintf(
                    'Migration "%s" should follow "%s" but no migration with #[StableMigration(\'%s\')] exists.',
                    $version,
                    $releaseVersion,
                    $releaseVersion
                )
            );
        }

        if (isset($this->resolving[$version])) {
            throw new LogicException(
                sprintf('Circular #[AfterStableRelease] reference involving migration "%s".', $version)
            );
        }

        $this->resolving[$version] = true;

        try {
            return $this->getSortKey($stableMigration) . '|' . $version;
        } finally {
            unset($this->resolving[$version]);
        }
    }
}
