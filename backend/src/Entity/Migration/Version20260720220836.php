<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260720220836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add playlist_chain column to station_queue and song_history.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_queue ADD playlist_chain JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE song_history ADD playlist_chain JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_queue DROP playlist_chain');
        $this->addSql('ALTER TABLE song_history DROP playlist_chain');
    }
}
