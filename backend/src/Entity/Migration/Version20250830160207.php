<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20250830160207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_simulcasting DROP created_at, DROP updated_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_simulcasting ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL');
    }
}
