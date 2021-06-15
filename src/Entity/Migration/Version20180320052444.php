<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180320052444 extends AbstractMigration
{
    public function preup(Schema $schema): void
    {
        // Avoid FK errors with station art
        $this->connection->executeStatement(
            'DELETE FROM station_media_art WHERE media_id NOT IN (SELECT id FROM station_media)'
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media_art ADD CONSTRAINT FK_35E0CAB2EA9FDD75 FOREIGN KEY (media_id) REFERENCES station_media (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media_art DROP FOREIGN KEY FK_35E0CAB2EA9FDD75');
    }
}
