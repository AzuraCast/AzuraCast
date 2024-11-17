<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240813181909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add playlist_media_id to station_queue';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_queue ADD playlist_media_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE station_queue ADD CONSTRAINT FK_277B005517421B18 FOREIGN KEY (playlist_media_id) REFERENCES station_playlist_media (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_277B005517421B18 ON station_queue (playlist_media_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_queue DROP FOREIGN KEY FK_277B005517421B18');
        $this->addSql('DROP INDEX IDX_277B005517421B18 ON station_queue');
        $this->addSql('ALTER TABLE station_queue DROP playlist_media_id');
    }
}
