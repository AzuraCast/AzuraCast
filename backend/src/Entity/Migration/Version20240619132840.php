<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240619132840 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate media metadata to an expandable field, pt. 2';
    }

    public function preUp(Schema $schema): void
    {
        $allMedia = $this->connection->iterateAssociativeIndexed(
            <<<SQL
                SELECT id, amplify, fade_start_next, fade_in, fade_out, cue_in, cue_out
                FROM station_media
                WHERE 
                    (amplify IS NOT NULL AND amplify != '')
                    OR (fade_start_next IS NOT NULL AND fade_start_next != '')
                    OR (fade_in IS NOT NULL AND fade_in != '')
                    OR (fade_out IS NOT NULL AND fade_out != '')
                    OR (cue_in IS NOT NULL AND cue_in != '')
                    OR (cue_out IS NOT NULL AND cue_out != '')
            SQL
        );

        $fieldLookup = [
            'amplify' => 'liq_amplify',
            'fade_start_next' => 'liq_cross_start_next',
            'fade_in' => 'liq_fade_in',
            'fade_out' => 'liq_fade_out',
            'cue_in' => 'liq_cue_in',
            'cue_out' => 'liq_cue_out',
        ];

        foreach ($allMedia as $id => $row) {
            $extraMeta = [];
            foreach ($fieldLookup as $original => $new) {
                if (isset($row[$original])) {
                    $originalVal = $row[$original];
                    if ('' !== $originalVal) {
                        $extraMeta[$new] = $originalVal;
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

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE station_media
                DROP amplify,
                DROP fade_start_next, 
                DROP fade_in, 
                DROP fade_out, 
                DROP cue_in, 
                DROP cue_out
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE station_media  
                ADD amplify NUMERIC(6, 1) DEFAULT NULL, 
                ADD fade_start_next NUMERIC(6, 1) DEFAULT NULL, 
                ADD fade_in NUMERIC(6, 1) DEFAULT NULL, 
                ADD fade_out NUMERIC(6, 1) DEFAULT NULL, 
                ADD cue_in NUMERIC(6, 1) DEFAULT NULL, 
                ADD cue_out NUMERIC(6, 1) DEFAULT NULL
        SQL);
    }
}
