<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210703185549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add columns to facilitate "loop once" functionality.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists ADD queue_reset_at INT NOT NULL');
        $this->addSql('ALTER TABLE station_schedules ADD loop_once TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists DROP queue_reset_at');
        $this->addSql('ALTER TABLE station_schedules DROP loop_once');
    }
}
