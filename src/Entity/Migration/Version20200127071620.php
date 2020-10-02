<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200127071620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new "station_streamer_broadcasts" table to track streamer historical broadcasts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE station_streamer_broadcasts (id INT AUTO_INCREMENT NOT NULL, station_id INT DEFAULT NULL, streamer_id INT DEFAULT NULL, timestamp_start INT NOT NULL, timestamp_end INT NOT NULL, recording_path VARCHAR(255) DEFAULT NULL, INDEX IDX_76169D6621BDB235 (station_id), INDEX IDX_76169D6625F432AD (streamer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE station_streamer_broadcasts ADD CONSTRAINT FK_76169D6621BDB235 FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_streamer_broadcasts ADD CONSTRAINT FK_76169D6625F432AD FOREIGN KEY (streamer_id) REFERENCES station_streamers (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE station_streamer_broadcasts');
    }
}
