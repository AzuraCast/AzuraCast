<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210416214621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move playlist queue to station_playlist_media table.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE station_playlist_media ADD is_queued TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE station_playlists DROP queue');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE station_playlist_media DROP is_queued');
        $this->addSql('ALTER TABLE station_playlists ADD queue LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'(DC2Type:array)\'');
    }
}
