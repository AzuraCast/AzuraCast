<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240706170405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds station mountpoint and hls streams limits';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD max_mounts TINYINT DEFAULT 0, ADD max_hls_streams TINYINT DEFAULT 0;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP max_bitrate, max_hls_streams');
    }
}
