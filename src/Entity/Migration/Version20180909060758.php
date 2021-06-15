<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove remote_ fields from station_mounts as they're now in their own table.
 */
final class Version20180909060758 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_mounts DROP remote_type, DROP remote_url, DROP remote_mount, DROP remote_source_username, DROP remote_source_password');
    }

    public function postup(Schema $schema): void
    {
        $stations = $this->connection->fetchAllAssociative("SELECT id FROM station WHERE frontend_type = 'remote'");

        foreach ($stations as $station) {
            $this->connection->delete('station_mounts', [
                'station_id' => $station['id'],
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_mounts ADD remote_type VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_general_ci, ADD remote_url VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_general_ci, ADD remote_mount VARCHAR(150) DEFAULT NULL COLLATE utf8mb4_general_ci, ADD remote_source_username VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_general_ci, ADD remote_source_password VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_general_ci');
    }
}
