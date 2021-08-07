<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170512094523 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media ADD fade_overlap NUMERIC(3, 1) DEFAULT NULL, ADD fade_in NUMERIC(3, 1) DEFAULT NULL, ADD fade_out NUMERIC(3, 1) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media DROP fade_overlap, DROP fade_in, DROP fade_out');
    }
}
