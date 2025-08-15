<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20250815004256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add description field to station_playlists table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists ADD description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists DROP description');
    }
}
