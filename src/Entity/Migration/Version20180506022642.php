<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180506022642 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_playlist_media ON station_playlist_media');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX idx_playlist_media ON station_playlist_media (playlist_id, media_id)');
    }
}
