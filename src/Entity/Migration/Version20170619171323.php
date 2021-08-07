<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170619171323 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media CHANGE cue_in cue_in NUMERIC(5, 1) DEFAULT NULL, CHANGE cue_out cue_out NUMERIC(5, 1) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media CHANGE cue_in cue_in NUMERIC(3, 1) DEFAULT NULL, CHANGE cue_out cue_out NUMERIC(3, 1) DEFAULT NULL');
    }
}
