<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

use const PASSWORD_ARGON2ID;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200129010322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Deduplicate streamers and hash passwords for streamer accounts.';
    }

    public function preUp(Schema $schema): void
    {
        // Deleting duplicate streamers to avoid constraint errors in subsequent update
        $streamers = $this->connection->fetchAllAssociative(
            'SELECT * FROM station_streamers ORDER BY station_id, id ASC'
        );
        $accounts = [];

        foreach ($streamers as $row) {
            $stationId = $row['station_id'];
            $username = $row['streamer_username'];

            if (isset($accounts[$stationId][$username])) {
                $this->connection->delete('station_streamers', ['id' => $row['id']]);
            } else {
                $accounts[$stationId][$username] = $username;
            }
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_streamers CHANGE streamer_password streamer_password VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX username_unique_idx ON station_streamers (station_id, streamer_username)');
    }

    public function postUp(Schema $schema): void
    {
        // Hash DJ passwords that are currently stored in plaintext.
        $streamers = $this->connection->fetchAllAssociative(
            'SELECT * FROM station_streamers ORDER BY station_id, id ASC'
        );

        foreach ($streamers as $row) {
            $this->connection->update('station_streamers', [
                'streamer_password' => password_hash($row['streamer_password'], PASSWORD_ARGON2ID),
            ], ['id' => $row['id']]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX username_unique_idx ON station_streamers');
        $this->addSql('ALTER TABLE station_streamers CHANGE streamer_password streamer_password VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`');
    }
}
