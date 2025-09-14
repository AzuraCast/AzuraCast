<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20250625164943 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add album to song-related fields.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE song_history ADD album VARCHAR(200) DEFAULT NULL AFTER title
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE station_queue ADD album VARCHAR(200) DEFAULT NULL AFTER title
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE song_history DROP album
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE station_queue DROP album
        SQL);
    }
}
