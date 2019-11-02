<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191101065730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE station_playlist_schedules (id INT AUTO_INCREMENT NOT NULL, playlist_id INT DEFAULT NULL, start_time SMALLINT NOT NULL, end_time SMALLINT NOT NULL, start_date VARCHAR(10) DEFAULT NULL, end_date VARCHAR(10) DEFAULT NULL, days VARCHAR(50) DEFAULT NULL, INDEX IDX_C61009BA6BBD148 (playlist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE station_playlist_schedules ADD CONSTRAINT FK_C61009BA6BBD148 FOREIGN KEY (playlist_id) REFERENCES station_playlists (id) ON DELETE CASCADE');
    }

    public function postUp(Schema $schema): void
    {
        $playlists = $this->connection->fetchAll(
            'SELECT sp.* FROM station_playlists AS sp WHERE sp.type = ?',
            ['scheduled'],
            [\PDO::PARAM_STR]
        );

        foreach ($playlists as $row) {
            $this->connection->insert('station_playlist_schedules', [
                'playlist_id' => $row['id'],
                'start_time' => $row['schedule_start_time'],
                'end_time' => $row['schedule_end_time'],
                'days' => $row['schedule_days'],
            ]);

            $this->connection->update('station_playlists', [
                'type' => 'default',
            ], [
                'id' => $row['id'],
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE station_playlist_schedules');
    }
}
