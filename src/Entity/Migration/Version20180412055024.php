<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Migration\Traits\UpdateAllRecords;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180412055024 extends AbstractMigration
{
    use UpdateAllRecords;

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists ADD source VARCHAR(50) NOT NULL, ADD include_in_requests TINYINT(1) NOT NULL');
    }

    public function postup(Schema $schema): void
    {
        $this->updateAllRecords('station_playlists', [
            'include_in_requests' => '1',
        ]);

        $this->updateAllRecords('station_playlists', [
            'source' => 'default',
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists DROP source, DROP include_in_requests');
    }
}
