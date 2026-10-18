<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260929120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add per-member opt-out for recursive queue reset of playlist groups.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE station_playlist_group ADD preserve_queue_on_recursive_reset TINYINT(1) NOT NULL DEFAULT 0'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlist_group DROP preserve_queue_on_recursive_reset');
    }
}
