<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use JsonException;

final class Version20240912014811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove cached extra metadata and re-standardize on non-prefixed meta names.';
    }

    public function up(Schema $schema): void
    {
        $this->migrateExtraMetadata([
            'amplify' => 'amplify',
            'liq_amplify' => 'amplify',
            'cross_start_next' => 'cross_start_next',
            'liq_cross_start_next' => 'cross_start_next',
            'fade_in' => 'fade_in',
            'liq_fade_in' => 'fade_in',
            'fade_out' => 'fade_out',
            'liq_fade_out' => 'fade_out',
            'cue_in' => 'cue_in',
            'liq_cue_in' => 'cue_in',
            'cue_out' => 'cue_out',
            'liq_cue_out' => 'cue_out',
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->migrateExtraMetadata([
            'amplify' => 'liq_amplify',
            'liq_amplify' => 'liq_amplify',
            'cross_start_next' => 'liq_cross_start_next',
            'liq_cross_start_next' => 'liq_cross_start_next',
            'fade_in' => 'liq_fade_in',
            'liq_fade_in' => 'liq_fade_in',
            'fade_out' => 'liq_fade_out',
            'liq_fade_out' => 'liq_fade_out',
            'cue_in' => 'liq_cue_in',
            'liq_cue_in' => 'liq_cue_in',
            'cue_out' => 'liq_cue_out',
            'liq_cue_out' => 'liq_cue_out',
        ]);
    }

    private function migrateExtraMetadata(array $fieldLookup): void
    {
        // Set null if array is empty.
        $this->connection->update(
            'station_media',
            [
                'extra_metadata' => null,
            ],
            [
                'extra_metadata' => '[]',
            ]
        );

        $allMedia = $this->connection->iterateKeyValue(
            <<<SQL
                SELECT id, extra_metadata
                FROM station_media
                WHERE extra_metadata IS NOT NULL AND extra_metadata != ''
            SQL
        );

        foreach ($allMedia as $id => $extraMetaRaw) {
            try {
                $row = json_decode($extraMetaRaw, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $row = [];
            }

            $extraMeta = [];

            if (count($row) > 0) {
                foreach ($fieldLookup as $original => $new) {
                    if (isset($row[$original])) {
                        $originalVal = $row[$original];
                        if ('' !== $originalVal) {
                            $extraMeta[$new] = $originalVal;
                        }
                    }
                }
            }

            $this->connection->update(
                'station_media',
                [
                    'extra_metadata' => (0 === count($extraMeta))
                        ? null
                        : json_encode($extraMeta, JSON_THROW_ON_ERROR),
                ],
                [
                    'id' => $id,
                ]
            );
        }
    }
}
