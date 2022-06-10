<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220610132810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove automation settings.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP automation_settings, DROP automation_timestamp');
        $this->addSql('ALTER TABLE station_playlists DROP include_in_automation');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE station ADD automation_settings LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD automation_timestamp INT DEFAULT NULL'
        );
        $this->addSql('ALTER TABLE station_playlists ADD include_in_automation TINYINT(1) NOT NULL');
    }
}
