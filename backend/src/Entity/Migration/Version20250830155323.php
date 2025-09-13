<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20250830155323 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_simulcasting CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE station_simulcasting RENAME INDEX idx_station_simulcasting_station TO IDX_575EA95521BDB235');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_simulcasting CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE station_simulcasting RENAME INDEX idx_575ea95521bdb235 TO IDX_STATION_SIMULCASTING_STATION');
    }
}
