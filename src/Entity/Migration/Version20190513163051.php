<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190513163051 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Move "play once per day" playlists to be standard scheduled ones with the same start/end time.
        $this->addSql('UPDATE station_playlists SET type="scheduled", schedule_start_time=play_once_time, schedule_end_time=play_once_time, schedule_days=play_once_days WHERE type = "once_per_day"');

        $this->addSql('ALTER TABLE station ADD timezone VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE station_playlists DROP play_once_time, DROP play_once_days');
        $this->addSql('ALTER TABLE users DROP timezone');
    }

    public function postup(Schema $schema): void
    {
        // Use the system setting for "global timezone" to set the station timezones.
        $global_tz = $this->connection->fetchOne('SELECT setting_value FROM settings WHERE setting_key="timezone"');

        if (!empty($global_tz)) {
            $global_tz = json_decode($global_tz, true, 512, JSON_THROW_ON_ERROR);
        } else {
            $global_tz = 'UTC';
        }

        // Set all stations' timezones to this value.
        $this->connection->update('station', [
            'timezone' => $global_tz,
        ], [1 => 1]);

        // Calculate the offset of any currently scheduled playlists.
        if ('UTC' !== $global_tz) {
            $system_tz = new DateTimeZone('UTC');
            $system_dt = new DateTime('now', $system_tz);
            $system_offset = $system_tz->getOffset($system_dt);

            $app_tz = new DateTimeZone($global_tz);
            $app_dt = new DateTime('now', $app_tz);
            $app_offset = $app_tz->getOffset($app_dt);

            $offset = $system_offset - $app_offset;
            $offset_hours = (int)floor($offset / 3600);

            if (0 !== $offset_hours) {
                $playlists = $this->connection->fetchAllAssociative(
                    'SELECT sp.* FROM station_playlists AS sp WHERE sp.type = "scheduled"'
                );

                foreach ($playlists as $playlist) {
                    $this->connection->update('station_playlists', [
                        'schedule_start_time' => $this->applyOffset($playlist['schedule_start_time'], $offset_hours),
                        'schedule_end_time' => $this->applyOffset($playlist['schedule_end_time'], $offset_hours),
                    ], [
                        'id' => $playlist['id'],
                    ]);
                }
            }
        }
    }

    /**
     * @param mixed $time_code
     * @param int $offset_hours
     *
     * @return int
     * @noinspection SummerTimeUnsafeTimeManipulationInspection
     */
    private function applyOffset(mixed $time_code, int $offset_hours): int
    {
        $hours = (int)floor($time_code / 100);
        $mins = $time_code % 100;

        $hours += $offset_hours;

        $hours %= 24;
        if ($hours < 0) {
            $hours += 24;
        }

        return ($hours * 100) + $mins;
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP timezone');
        $this->addSql('ALTER TABLE station_playlists ADD play_once_time SMALLINT NOT NULL, ADD play_once_days VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_general_ci');
        $this->addSql('ALTER TABLE users ADD timezone VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_general_ci');
    }
}
