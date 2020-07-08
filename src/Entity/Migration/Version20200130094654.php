<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200130094654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create station_playlist_folders table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE station_playlist_folders (id INT AUTO_INCREMENT NOT NULL, station_id INT DEFAULT NULL, playlist_id INT DEFAULT NULL, path VARCHAR(255) NOT NULL, INDEX IDX_15190AE921BDB235 (station_id), INDEX IDX_15190AE96BBD148 (playlist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE station_playlist_folders ADD CONSTRAINT FK_15190AE921BDB235 FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_playlist_folders ADD CONSTRAINT FK_15190AE96BBD148 FOREIGN KEY (playlist_id) REFERENCES station_playlists (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE station_playlist_folders');
    }
}
