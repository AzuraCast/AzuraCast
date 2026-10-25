<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ;

use App\Service\PlaylistConfiguration\Schema\PlaylistConfigurationSchema;
use App\Tests\AutoDJ\Scenario\Enums\ScenarioMode;
use App\Tests\AutoDJ\Scenario\ScenarioCase;
use App\Tests\AutoDJ\Scenario\ScenarioFile;
use RuntimeException;

use const JSON_THROW_ON_ERROR;

/**
 * Loads AutoDJ test fixtures from "tests/_data/autodj/".
 * Each scenario file ("*.scenario.json") has a dump file ("*.dump.json").
 *
 * The dump is a playlist configuration created by the "PlaylistConfigurationExporter"
 * and the scenario contains the frozen "now", runtime overrides and expectations for the test.
 *
 * Class is used as a PHPUnit data provider source to yield one row per scenario case.
 *
 * @phpstan-import-type PlaylistConfigurationDump from PlaylistConfigurationSchema
 *
 * @phpstan-type ProviderRow array{
 *     dump: PlaylistConfigurationDump,
 *     case: ScenarioCase,
 *     description: ?string
 * }
 */
final class DumpLoader
{
    /**
     * @return array<string, ProviderRow> Keyed by "label"
     */
    public static function providerForMode(ScenarioMode $mode): array
    {
        $rows = [];

        foreach (self::scenarioFiles() as $scenarioPath) {
            $scenario = ScenarioFile::fromArray(self::decodeFile($scenarioPath));

            if (!in_array($mode, $scenario->modes, true)) {
                continue;
            }

            /** @var PlaylistConfigurationDump $dump */
            $dump = self::decodeFile(self::dumpPathFor($scenarioPath));
            $baseLabel = basename($scenarioPath, '.scenario.json');

            $descriptionSuffix = !empty($scenario->description)
                ? ' — ' . $scenario->description
                : '';

            foreach ($scenario->cases as $index => $case) {
                $caseName = $case->name ?? ('case_' . $index);

                $rows["{$baseLabel} :: {$caseName}{$descriptionSuffix}"] = [
                    'dump' => $dump,
                    'case' => $case,
                    'description' => $scenario->description,
                ];
            }
        }

        return $rows;
    }

    /**
     * @return string[]
     */
    private static function scenarioFiles(): array
    {
        $dataDir = self::dataDir();

        $files = glob($dataDir . '/*/*.scenario.json') ?: [];
        sort($files);

        return $files;
    }

    private static function dumpPathFor(string $scenarioPath): string
    {
        return (string) preg_replace('/\.scenario\.json$/', '.dump.json', $scenarioPath);
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeFile(string $path): array
    {
        $contents = file_get_contents($path);
        if (false === $contents) {
            throw new RuntimeException(sprintf('Could not read fixture file "%s".', $path));
        }

        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException(sprintf('Fixture file "%s" is not a JSON object.', $path));
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    private static function dataDir(): string
    {
        return rtrim(codecept_data_dir('autodj'), '/');
    }
}
