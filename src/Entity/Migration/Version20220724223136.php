<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220724223136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_history DROP FOREIGN KEY FK_2AD16164427EB8A5');
        $this->addSql('ALTER TABLE song_history DROP FOREIGN KEY FK_2AD1616425F432AD');
        $this->addSql('ALTER TABLE song_history DROP FOREIGN KEY FK_2AD161646BBD148');
        $this->addSql('ALTER TABLE song_history ADD CONSTRAINT FK_2AD16164427EB8A5 FOREIGN KEY (request_id) REFERENCES station_requests (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE song_history ADD CONSTRAINT FK_2AD1616425F432AD FOREIGN KEY (streamer_id) REFERENCES station_streamers (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE song_history ADD CONSTRAINT FK_2AD161646BBD148 FOREIGN KEY (playlist_id) REFERENCES station_playlists (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_history DROP FOREIGN KEY FK_2AD161646BBD148');
        $this->addSql('ALTER TABLE song_history DROP FOREIGN KEY FK_2AD1616425F432AD');
        $this->addSql('ALTER TABLE song_history DROP FOREIGN KEY FK_2AD16164427EB8A5');
        $this->addSql('ALTER TABLE song_history ADD CONSTRAINT FK_2AD161646BBD148 FOREIGN KEY (playlist_id) REFERENCES station_playlists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_history ADD CONSTRAINT FK_2AD1616425F432AD FOREIGN KEY (streamer_id) REFERENCES station_streamers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_history ADD CONSTRAINT FK_2AD16164427EB8A5 FOREIGN KEY (request_id) REFERENCES station_requests (id) ON DELETE CASCADE');
    }
}
