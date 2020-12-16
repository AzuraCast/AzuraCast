<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201003021913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Songs denormalization, part 1';
    }

    public function up(Schema $schema): void
    {
        // Handling potentially failed updates.
        $this->addSql('ALTER TABLE song_history DROP COLUMN IF EXISTS `text`, DROP COLUMN IF EXISTS `artist`, DROP COLUMN IF EXISTS `title`');

        // Avoid "data truncated" errors with really long titles/artists.
        $this->addSql('UPDATE station_media SET artist=SUBSTRING(artist, 1, 150), title=SUBSTRING(title, 1, 150)');

        $this->addSql('ALTER TABLE song_history ADD IF NOT EXISTS `text` VARCHAR(150) DEFAULT NULL, ADD IF NOT EXISTS artist VARCHAR(150) DEFAULT NULL, ADD IF NOT EXISTS title VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE station_media ADD IF NOT EXISTS `text` VARCHAR(150) DEFAULT NULL, CHANGE title title VARCHAR(150) DEFAULT NULL, CHANGE artist artist VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE station_queue ADD IF NOT EXISTS `text` VARCHAR(150) DEFAULT NULL, ADD IF NOT EXISTS artist VARCHAR(150) DEFAULT NULL, ADD IF NOT EXISTS title VARCHAR(150) DEFAULT NULL');

        $this->addSql('UPDATE song_history sh JOIN songs s ON sh.song_id = s.id SET sh.text=s.text, sh.artist=s.artist, sh.title=s.title');
        $this->addSql('UPDATE station_queue sq JOIN songs s ON sq.song_id = s.id SET sq.text=s.text, sq.artist=s.artist, sq.title=s.title');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_history DROP `text`, DROP artist, DROP title');
        $this->addSql('ALTER TABLE station_media DROP `text`, CHANGE artist artist VARCHAR(200) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, CHANGE title title VARCHAR(200) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`');
        $this->addSql('ALTER TABLE station_queue DROP `text`, DROP artist, DROP title');
    }
}
