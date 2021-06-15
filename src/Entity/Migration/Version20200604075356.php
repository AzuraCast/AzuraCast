<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200604075356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add per-playlist "avoid duplicates" flag.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists ADD avoid_duplicates TINYINT(1) NOT NULL');
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->executeStatement('UPDATE station_playlists SET avoid_duplicates=1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists DROP avoid_duplicates');
    }
}
