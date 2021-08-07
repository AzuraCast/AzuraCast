<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170512023527 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song_history ADD playlist_id INT DEFAULT NULL, ADD timestamp_cued INT DEFAULT NULL');
        $this->addSql('ALTER TABLE song_history ADD CONSTRAINT FK_2AD161646BBD148 FOREIGN KEY (playlist_id) REFERENCES station_playlists (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_2AD161646BBD148 ON song_history (playlist_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song_history DROP FOREIGN KEY FK_2AD161646BBD148');
        $this->addSql('DROP INDEX IDX_2AD161646BBD148 ON song_history');
        $this->addSql('ALTER TABLE song_history DROP playlist_id, DROP timestamp_cued');
    }
}
