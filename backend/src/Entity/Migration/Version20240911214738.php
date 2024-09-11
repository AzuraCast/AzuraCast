<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240911214738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make recent DB changes idempotent by Doctrine Migrations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station CHANGE max_bitrate max_bitrate SMALLINT DEFAULT 0 NOT NULL, CHANGE max_mounts max_mounts SMALLINT DEFAULT 0 NOT NULL, CHANGE max_hls_streams max_hls_streams SMALLINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station CHANGE max_bitrate max_bitrate INT DEFAULT 0, CHANGE max_mounts max_mounts TINYINT(1) DEFAULT 0, CHANGE max_hls_streams max_hls_streams TINYINT(1) DEFAULT 0');
    }
}
