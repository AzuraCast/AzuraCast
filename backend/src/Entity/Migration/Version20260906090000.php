<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260906090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add per-playlist opt-out for queue reset on station restart.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists ADD preserve_queue_on_restart TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists DROP preserve_queue_on_restart');
    }
}
