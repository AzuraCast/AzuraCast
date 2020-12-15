<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201215175111 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand the possible length of various cue and fade values in StationMedia.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE station_media CHANGE amplify amplify NUMERIC(6, 1) DEFAULT NULL, CHANGE fade_overlap fade_overlap NUMERIC(6, 1) DEFAULT NULL, CHANGE fade_in fade_in NUMERIC(6, 1) DEFAULT NULL, CHANGE fade_out fade_out NUMERIC(6, 1) DEFAULT NULL, CHANGE cue_in cue_in NUMERIC(6, 1) DEFAULT NULL, CHANGE cue_out cue_out NUMERIC(6, 1) DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE station_media CHANGE amplify amplify NUMERIC(3, 1) DEFAULT NULL, CHANGE fade_overlap fade_overlap NUMERIC(3, 1) DEFAULT NULL, CHANGE fade_in fade_in NUMERIC(3, 1) DEFAULT NULL, CHANGE fade_out fade_out NUMERIC(3, 1) DEFAULT NULL, CHANGE cue_in cue_in NUMERIC(5, 1) DEFAULT NULL, CHANGE cue_out cue_out NUMERIC(5, 1) DEFAULT NULL'
        );
    }
}
