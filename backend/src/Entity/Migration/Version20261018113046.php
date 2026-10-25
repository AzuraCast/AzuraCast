<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20261018113046 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Unlink playlists from manual podcasts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            UPDATE podcast
            SET playlist_id = NULL
            WHERE
                source = 'manual'
                AND playlist_id IS NOT NULL
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        // Noop
    }
}
