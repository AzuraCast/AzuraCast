<?php declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190513124232 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // Move "play once per day" playlists to be standard scheduled ones with the same start/end time.
        $this->addSql('UPDATE station_playlists SET type="scheduled", schedule_start_time=play_once_time, schedule_end_time=play_once_time, schedule_days=play_once_days WHERE type = "once_per_day"');

        $this->addSql('ALTER TABLE station_playlists ADD schedule_tz VARCHAR(100) DEFAULT NULL, DROP play_once_time, DROP play_once_days');

        // Set all legacy playlists to be scheduled against UTC for back-compatibility.
        $this->addSql('UPDATE station_playlists SET schedule_tz="UTC" WHERE type = "scheduled"');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE station_playlists ADD play_once_time SMALLINT NOT NULL, ADD play_once_days VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_general_ci, DROP schedule_tz');
    }
}
