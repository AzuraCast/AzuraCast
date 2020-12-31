<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201231011833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make paths across the system consistent.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media CHANGE path path VARCHAR(500) NOT NULL');
        $this->addSql('ALTER TABLE station_playlist_folders CHANGE path path VARCHAR(500) NOT NULL');
        $this->addSql('ALTER TABLE unprocessable_media CHANGE path path VARCHAR(500) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE station_media CHANGE path path VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`'
        );
        $this->addSql(
            'ALTER TABLE station_playlist_folders CHANGE path path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`'
        );
        $this->addSql(
            'ALTER TABLE unprocessable_media CHANGE path path VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`'
        );
    }
}
