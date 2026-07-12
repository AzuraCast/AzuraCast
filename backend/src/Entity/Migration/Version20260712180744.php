<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260712180744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add play_full_cycle column to station_playlist_group.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlist_group ADD play_full_cycle TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlist_group DROP play_full_cycle');
    }
}
