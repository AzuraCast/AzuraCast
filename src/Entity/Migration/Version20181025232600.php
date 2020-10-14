<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add reactivate_at to station_streamers table
 */
final class Version20181025232600 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD disconnect_deactivate_streamer INT DEFAULT 0');
        $this->addSql('ALTER TABLE station_streamers ADD reactivate_at INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_streamers DROP reactivate_at');
        $this->addSql('ALTER TABLE station DROP disconnect_deactivate_streamer');
    }
}
