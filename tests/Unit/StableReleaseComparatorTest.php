<?php

declare(strict_types=1);

namespace Unit;

use App\Doctrine\Migrations\StableReleaseComparator;
use App\Entity\Attributes\AfterStableRelease;
use App\Entity\Attributes\StableMigration;
use App\Entity\Migration\Version20220605052847;
use App\Entity\Migration\Version20240702170603;
use App\Entity\Migration\Version20240706170405;
use App\Entity\Migration\Version20241113155508;
use App\Entity\Migration\Version20250920195939;
use App\Entity\Migration\Version20260408060000;
use App\Entity\Migration\Version20260426132842;
use App\Entity\Migration\Version20260501120000;
use App\Entity\Migration\Version20260525120000;
use App\Entity\Migration\Version20260712180744;
use App\Entity\Migration\Version20260720220836;
use App\Entity\Migration\Version20260807193430;
use App\Entity\Migration\Version20260905144030;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Doctrine\Migrations\Version\Version;
use ReflectionClass;

final class StableReleaseComparatorTest extends Unit
{
    private const string MIGRATIONS_DIR = __DIR__ . '/../../backend/src/Entity/Migration';

    private StableReleaseComparator $comparator;

    protected function _before(): void
    {
        $this->comparator = new StableReleaseComparator(self::MIGRATIONS_DIR);
    }

    /**
     * @return array<string, array{string, list<class-string>, class-string}>
     */
    public static function lateMergedMigrationProvider(): array
    {
        return [
            '0.23.8 (playlist groups)' => [
                '0.23.8',
                [
                    Version20250920195939::class,
                    Version20260426132842::class,
                    Version20260501120000::class,
                    Version20260525120000::class,
                    Version20260712180744::class,
                    Version20260720220836::class,
                ],
                Version20260905144030::class,
            ],
            '0.20.2 (station limits)' => [
                '0.20.2',
                [
                    Version20240702170603::class,
                    Version20240706170405::class,
                ],
                Version20241113155508::class,
            ],
        ];
    }

    /**
     * @param list<class-string> $lateMigrations
     */
    #[DataProvider('lateMergedMigrationProvider')]
    public function testLateMergedMigrationsSortAfterTheirRelease(
        string $release,
        array $lateMigrations,
        string $nextMigration
    ): void {
        $stableMigration = $this->comparator->getStableMigration($release);
        self::assertNotNull($stableMigration);

        foreach ($lateMigrations as $lateMigration) {
            // By name alone, these would count as part of the release.
            self::assertLessThan(0, strcmp($lateMigration, $stableMigration));

            self::assertGreaterThan(0, $this->compare($lateMigration, $stableMigration));
            self::assertLessThan(0, $this->compare($lateMigration, $nextMigration));
        }
    }

    public function testUntaggedMigrationsKeepAlphabeticalOrder(): void
    {
        $pairs = [
            [Version20260807193430::class, Version20260905144030::class],
            [Version20250920195939::class, Version20260426132842::class],
            // A no longer existing app migration
            [StableReleaseComparator::APP_MIGRATIONS_NAMESPACE . 'Version20190513124232', Version20220605052847::class],
            // A Plugin migration
            [Version20260905144030::class, 'Plugin\\Example\\Migration\\Version20200101000000'],
        ];

        foreach ($pairs as [$a, $b]) {
            self::assertLessThan(0, $this->compare($a, $b), sprintf('%s < %s', $a, $b));
            self::assertGreaterThan(0, $this->compare($b, $a), sprintf('%s > %s', $b, $a));
        }

        self::assertSame(0, $this->compare(Version20250920195939::class, Version20250920195939::class));
    }

    public function testGetStableMigration(): void
    {
        self::assertSame(Version20260807193430::class, $this->comparator->getStableMigration('0.23.8'));
        self::assertSame(Version20260408060000::class, $this->comparator->getStableMigration('0.23.7'));
        self::assertNull($this->comparator->getStableMigration('0.99.0'));
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function migrationsToRevertProvider(): array
    {
        return [
            '0.23.8' => [
                '0.23.8',
                [
                    Version20260905144030::class,
                    Version20260720220836::class,
                    Version20250920195939::class,
                ],
            ],
            // By name, Version20250920195939 would count as part of 0.23.7 and not be reverted.
            '0.23.7' => [
                '0.23.7',
                [
                    Version20260905144030::class,
                    Version20260720220836::class,
                    Version20250920195939::class,
                    Version20260807193430::class,
                ],
            ],
        ];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('migrationsToRevertProvider')]
    public function testGetMigrationsToRevert(string $release, array $expected): void
    {
        $stableMigration = $this->comparator->getStableMigration($release);
        self::assertNotNull($stableMigration);

        $executedMigrations = [
            Version20250920195939::class,
            'Plugin\\Example\\Migration\\Version20990101000000',
            Version20220605052847::class,
            Version20260905144030::class,
            // A no longer existing app migration which would otherwise sort after every release
            StableReleaseComparator::APP_MIGRATIONS_NAMESPACE . 'Version20990101000000',
            Version20260807193430::class,
            Version20260408060000::class,
            Version20260720220836::class,
        ];

        self::assertSame(
            $expected,
            $this->comparator->getMigrationsToRevert($executedMigrations, $stableMigration)
        );
    }

    public function testMarkersAndTagsAreConsistent(): void
    {
        $stableVersions = [];

        foreach (self::getAllMigrations() as $migration) {
            $reflection = new ReflectionClass($migration);

            foreach ($reflection->getAttributes(StableMigration::class) as $attribute) {
                $version = $attribute->newInstance()->version;

                self::assertArrayNotHasKey(
                    $version,
                    $stableVersions,
                    sprintf('Stable version %s is marked on more than one migration.', $version)
                );
                $stableVersions[$version] = $migration;
            }

            foreach ($reflection->getAttributes(AfterStableRelease::class) as $attribute) {
                $version = $attribute->newInstance()->version;
                $stableMigration = $this->comparator->getStableMigration($version);

                self::assertNotNull(
                    $stableMigration,
                    sprintf('%s follows stable version %s, which has no marker.', $migration, $version)
                );
                self::assertGreaterThan(0, $this->compare($migration, $stableMigration));
            }
        }
    }

    private function compare(string $a, string $b): int
    {
        return $this->comparator->compare(new Version($a), new Version($b));
    }

    /**
     * @return list<class-string>
     */
    private static function getAllMigrations(): array
    {
        $migrations = [];
        foreach (glob(self::MIGRATIONS_DIR . '/Version*.php') ?: [] as $file) {
            /** @var class-string $className */
            $className = StableReleaseComparator::APP_MIGRATIONS_NAMESPACE . basename($file, '.php');
            $migrations[] = $className;
        }

        return $migrations;
    }
}
