<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200310204315 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track the current streamer for every song history event.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song_history ADD streamer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE song_history ADD CONSTRAINT FK_2AD1616425F432AD FOREIGN KEY (streamer_id) REFERENCES station_streamers (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_2AD1616425F432AD ON song_history (streamer_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song_history DROP FOREIGN KEY FK_2AD1616425F432AD');
        $this->addSql('DROP INDEX IDX_2AD1616425F432AD ON song_history');
        $this->addSql('ALTER TABLE song_history DROP streamer_id');
    }
}
