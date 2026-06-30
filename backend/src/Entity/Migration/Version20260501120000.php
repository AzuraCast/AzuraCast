<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260501120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add consecutive plays tracking to playlist group members.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlist_group ADD consecutive_plays INT NOT NULL DEFAULT 0, ADD consecutive_plays_count INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlist_group DROP consecutive_plays, DROP consecutive_plays_count');
    }
}
