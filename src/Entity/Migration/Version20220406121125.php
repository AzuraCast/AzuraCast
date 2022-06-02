<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220406121125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reduce size and improve performance of StationQueue table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_queue DROP IF EXISTS log');
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->executeQuery(
            <<<SQL
                DELETE FROM station_queue WHERE timestamp_played < (UNIX_TIMESTAMP() - (86400 * 14))
            SQL
        );

        $this->connection->executeQuery(
            <<<SQL
                OPTIMIZE TABLE station_queue
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE station_queue ADD log LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'(DC2Type:json)\''
        );
    }
}
