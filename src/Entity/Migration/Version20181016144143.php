<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Manually re-shuffle any "shuffled" playlists via their weights in the DB.
 */
final class Version20181016144143 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('-- "Ignore Migration"');
    }

    public function postup(Schema $schema): void
    {
        $shuffledPlaylists = $this->connection->fetchAllAssociative(
            'SELECT sp.* FROM station_playlists AS sp WHERE sp.playback_order = :order',
            [
                'order' => 'shuffle',
            ]
        );

        foreach ($shuffledPlaylists as $playlist) {
            $allMedia = $this->connection->fetchAllAssociative(
                'SELECT spm.* FROM station_playlist_media AS spm WHERE spm.playlist_id = :playlist_id ORDER BY RAND()',
                [
                    'playlist_id' => $playlist['id'],
                ]
            );

            $weight = 1;
            foreach ($allMedia as $row) {
                $this->connection->update('station_playlist_media', [
                    'weight' => $weight,
                ], [
                    'id' => $row['id'],
                ]);

                $weight++;
            }
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('-- "Ignore Migration"');
    }
}
