<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220102033308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'New Settings columns for new sync process.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings ADD sync_disabled TINYINT(1) NOT NULL, ADD sync_last_run INT NOT NULL, DROP nowplaying, DROP sync_nowplaying_last_run, DROP sync_short_last_run, DROP sync_medium_last_run, DROP sync_long_last_run');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings ADD nowplaying LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'(DC2Type:json)\', ADD sync_short_last_run INT NOT NULL, ADD sync_medium_last_run INT NOT NULL, ADD sync_long_last_run INT NOT NULL, DROP sync_disabled, CHANGE sync_last_run sync_nowplaying_last_run INT NOT NULL');
    }
}
