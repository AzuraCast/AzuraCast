<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190429025906 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists ADD backend_options VARCHAR(255) DEFAULT NULL');
    }

    public function postup(Schema $schema): void
    {
        $playlists = $this->connection->fetchAllAssociative('SELECT sp.* FROM station_playlists AS sp');

        foreach ($playlists as $playlist) {
            $backend_options = [];

            if ($playlist['interrupt_other_songs']) {
                $backend_options[] = 'interrupt';
            }
            if ($playlist['loop_playlist_once']) {
                $backend_options[] = 'loop_once';
            }
            if ($playlist['play_single_track']) {
                $backend_options[] = 'single_track';
            }

            $this->connection->update('station_playlists', [
                'backend_options' => implode(',', $backend_options),
            ], [
                'id' => $playlist['id'],
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists DROP backend_options');
    }
}
