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
        $globalTz = $this->connection->fetchOne('SELECT setting_value FROM settings WHERE setting_key="timezone"');

        if (!empty($globalTz)) {
            $globalTz = json_decode($globalTz, true, 512, JSON_THROW_ON_ERROR);
        } else {
            $globalTz = 'UTC';
        }

        // Set all stations' timezones to this value.
        $this->connection->update('station', [
            'timezone' => $globalTz,
        ], [1 => 1]);

        // Calculate the offset of any currently scheduled playlists.
        if ('UTC' !== $globalTz) {
            $systemTz = new DateTimeZone('UTC');
            $systemDt = new DateTime('now', $systemTz);
            $systemOffset = $systemTz->getOffset($systemDt);

            $appTz = new DateTimeZone($globalTz);
            $appDt = new DateTime('now', $appTz);
            $appOffset = $appTz->getOffset($appDt);

            $offset = $systemOffset - $appOffset;
            $offsetHours = (int)floor($offset / 3600);

            if (0 !== $offsetHours) {
                $playlists = $this->connection->fetchAllAssociative(
                    'SELECT sp.* FROM station_playlists AS sp WHERE sp.type = "scheduled"'
                );

                foreach ($playlists as $playlist) {
                    $this->connection->update('station_playlists', [
                        'schedule_start_time' => $this->applyOffset($playlist['schedule_start_time'], $offsetHours),
                        'schedule_end_time' => $this->applyOffset($playlist['schedule_end_time'], $offsetHours),
                    ], [
                        'id' => $playlist['id'],
                    ]);
                }
            }
        }
    }

    /**
     * @param mixed $timeCode
     * @param int $offsetHours
     *
     * @return int
     * @noinspection SummerTimeUnsafeTimeManipulationInspection
     */
    private function applyOffset(mixed $timeCode, int $offsetHours): int
    {
        $hours = (int)floor($timeCode / 100);
        $mins = $timeCode % 100;

        $hours += $offsetHours;

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
