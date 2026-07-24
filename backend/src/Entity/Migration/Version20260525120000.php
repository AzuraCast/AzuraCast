<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260525120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add allowed_requests column to station_playlist_group.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE station_playlist_group ADD allowed_requests VARCHAR(255) NOT NULL DEFAULT 'any'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlist_group DROP allowed_requests');
    }
}
