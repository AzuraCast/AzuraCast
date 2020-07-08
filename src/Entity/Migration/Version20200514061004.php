<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200514061004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "on-demand" options for stations and playlists.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD enable_on_demand TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE station_playlists ADD include_in_on_demand TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP enable_on_demand');
        $this->addSql('ALTER TABLE station_playlists DROP include_in_on_demand');
    }
}
