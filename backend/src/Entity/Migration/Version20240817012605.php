<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240817012605 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add priority field to station_playlists';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists ADD priority SMALLINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists DROP priority');
    }
}
