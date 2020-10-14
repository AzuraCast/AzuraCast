<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add source_port, source_mount to station_remotes table
 */
final class Version20180909174026 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_remotes ADD source_port SMALLINT DEFAULT NULL, ADD source_mount VARCHAR(150) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_remotes DROP source_port, DROP source_mount');
    }
}
