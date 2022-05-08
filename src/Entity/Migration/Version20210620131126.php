<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210620131126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "max_listener_duration" to station_mounts table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_mounts ADD max_listener_duration INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_mounts DROP max_listener_duration');
    }
}
