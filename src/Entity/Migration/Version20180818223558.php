<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add per-station-configurable number of history items to be shown in the NowPlaying API.
 */
final class Version20180818223558 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD api_history_items SMALLINT NOT NULL');
    }

    public function postup(Schema $schema): void
    {
        $this->connection->update('station', [
            'api_history_items' => 5,
        ], [1 => 1]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP api_history_items');
    }
}
