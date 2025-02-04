<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20250203160744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Improve precision of several date/duration fields.';
    }

    protected function migrateForwardsToDateTime(
        string $tableName,
        string $fieldName,
        bool $nullable
    ): void {
        $tableName = $this->connection->quoteIdentifier($tableName);

        $tempFieldName = $this->connection->quoteIdentifier('temp_' . $fieldName);
        $destFieldName = $this->connection->quoteIdentifier($fieldName);

        $this->addSql(
            <<<SQL
                ALTER TABLE $tableName RENAME COLUMN $destFieldName TO $tempFieldName,
                    ADD COLUMN $destFieldName DATETIME(6) DEFAULT NULL AFTER $tempFieldName
            SQL
        );

        $this->addSql(
            <<<SQL
                UPDATE $tableName
                    SET $destFieldName=IF($tempFieldName = 0, NULL, FROM_UNIXTIME($tempFieldName))
            SQL
        );

        if (!$nullable) {
            $this->addSql("DELETE FROM $tableName WHERE $destFieldName IS NULL");
            $this->addSql("ALTER TABLE $tableName CHANGE $destFieldName $destFieldName DATETIME(6) NOT NULL");
        }

        $this->addSql("ALTER TABLE $tableName DROP $tempFieldName");
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_timestamp_start ON song_history');
        $this->addSql('DROP INDEX IF EXISTS idx_timestamp_end ON song_history');
        $this->addSql('DROP INDEX IF EXISTS idx_timestamp_played ON station_queue');
        $this->addSql('DROP INDEX IF EXISTS idx_timestamp_cued ON station_queue');
        $this->addSql('DROP INDEX IF EXISTS idx_timestamps ON listener');

        $this->migrateForwardsToDateTime('audit_log', 'timestamp', false);
        $this->migrateForwardsToDateTime('listener', 'timestamp_start', false);
        $this->migrateForwardsToDateTime('listener', 'timestamp_end', true);
        $this->migrateForwardsToDateTime('relays', 'created_at', false);
        $this->migrateForwardsToDateTime('relays', 'updated_at', false);
        $this->migrateForwardsToDateTime('song_history', 'timestamp_start', false);
        $this->migrateForwardsToDateTime('song_history', 'timestamp_end', true);
        $this->migrateForwardsToDateTime('station_queue', 'timestamp_cued', false);
        $this->migrateForwardsToDateTime('station_queue', 'timestamp_played', true);
        $this->migrateForwardsToDateTime('station_requests', 'timestamp', false);
        $this->migrateForwardsToDateTime('station_requests', 'played_at', true);
        $this->migrateForwardsToDateTime('station_streamer_broadcasts', 'timestamp_start', false);
        $this->migrateForwardsToDateTime('station_streamer_broadcasts', 'timestamp_end', true);

        $this->addSql('CREATE INDEX idx_timestamps ON listener (timestamp_end, timestamp_start)');
        $this->addSql('CREATE INDEX idx_timestamp_start ON song_history (timestamp_start)');
        $this->addSql('CREATE INDEX idx_timestamp_end ON song_history (timestamp_end)');
        $this->addSql('CREATE INDEX idx_timestamp_played ON station_queue (timestamp_played)');
        $this->addSql('CREATE INDEX idx_timestamp_cued ON station_queue (timestamp_cued)');

        $this->addSql('ALTER TABLE song_history CHANGE duration duration DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE station_media CHANGE length length DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE station_queue CHANGE duration duration DOUBLE PRECISION DEFAULT NULL');
    }

    protected function migrateBackFromDateTime(
        string $tableName,
        string $fieldName
    ): void {
        $tableName = $this->connection->quoteIdentifier($tableName);

        $tempFieldName = $this->connection->quoteIdentifier('temp_' . $fieldName);
        $destFieldName = $this->connection->quoteIdentifier($fieldName);

        $this->addSql(
            <<<SQL
                ALTER TABLE $tableName 
                    RENAME COLUMN $destFieldName TO $tempFieldName,
                    ADD COLUMN $destFieldName INT DEFAULT NULL AFTER $tempFieldName
            SQL
        );

        $this->addSql(
            "UPDATE $tableName SET $destFieldName=IF($tempFieldName IS NULL, 0, UNIX_TIMESTAMP($tempFieldName))"
        );

        $this->addSql(
            <<<SQL
                ALTER TABLE $tableName 
                    CHANGE $destFieldName $destFieldName INT NOT NULL,
                    DROP $tempFieldName
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_timestamp_start ON song_history');
        $this->addSql('DROP INDEX IF EXISTS idx_timestamp_end ON song_history');
        $this->addSql('DROP INDEX IF EXISTS idx_timestamp_played ON station_queue');
        $this->addSql('DROP INDEX IF EXISTS idx_timestamp_cued ON station_queue');
        $this->addSql('DROP INDEX IF EXISTS idx_timestamps ON listener');

        $this->migrateBackFromDateTime('song_history', 'timestamp_start');
        $this->migrateBackFromDateTime('song_history', 'timestamp_end');
        $this->migrateBackFromDateTime('audit_log', 'timestamp');
        $this->migrateBackFromDateTime('station_requests', 'timestamp');
        $this->migrateBackFromDateTime('station_requests', 'played_at');
        $this->migrateBackFromDateTime('station_streamer_broadcasts', 'timestamp_start');
        $this->migrateBackFromDateTime('station_streamer_broadcasts', 'timestamp_end');
        $this->migrateBackFromDateTime('station_queue', 'timestamp_cued');
        $this->migrateBackFromDateTime('station_queue', 'timestamp_played');
        $this->migrateBackFromDateTime('listener', 'timestamp_start');
        $this->migrateBackFromDateTime('listener', 'timestamp_end');
        $this->migrateBackFromDateTime('relays', 'created_at');
        $this->migrateBackFromDateTime('relays', 'updated_at');

        $this->addSql('CREATE INDEX idx_timestamps ON listener (timestamp_end, timestamp_start)');
        $this->addSql('CREATE INDEX idx_timestamp_start ON song_history (timestamp_start)');
        $this->addSql('CREATE INDEX idx_timestamp_end ON song_history (timestamp_end)');
        $this->addSql('CREATE INDEX idx_timestamp_played ON station_queue (timestamp_played)');
        $this->addSql('CREATE INDEX idx_timestamp_cued ON station_queue (timestamp_cued)');

        $this->addSql('ALTER TABLE song_history CHANGE duration duration INT DEFAULT NULL');
        $this->addSql('ALTER TABLE station_media CHANGE length length NUMERIC(7, 2) NOT NULL');
        $this->addSql('ALTER TABLE station_queue CHANGE duration duration INT DEFAULT NULL');
    }
}
