<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Move all playlists that were previously "random" into the new "shuffled" type.
 */
final class Version20180830003036 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('-- "Ignore Migration"');
    }

    public function postup(Schema $schema): void
    {
        $this->connection->update('station_playlists', [
            'playback_order' => 'shuffle',
        ], [
            'playback_order' => 'random',
        ]);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('-- "Ignore Migration"');
    }

    public function postdown(Schema $schema): void
    {
        $this->connection->update('station_playlists', [
            'playback_order' => 'random',
        ], [
            'playback_order' => 'shuffle',
        ]);
    }
}
