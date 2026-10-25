<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20261003120502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop column defaults from station_playlist_group since they are not needed anymore.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
                ALTER TABLE station_playlist_group
                    ALTER consecutive_plays DROP DEFAULT,
                    ALTER consecutive_plays_count DROP DEFAULT,
                    ALTER allowed_requests DROP DEFAULT,
                    ALTER play_full_cycle DROP DEFAULT
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
                ALTER TABLE station_playlist_group
                    ALTER consecutive_plays SET DEFAULT 0,
                    ALTER consecutive_plays_count SET DEFAULT 0,
                    ALTER allowed_requests SET DEFAULT 'any',
                    ALTER play_full_cycle SET DEFAULT 0
            SQL
        );
    }
}
