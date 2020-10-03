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
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_history ADD text VARCHAR(150) DEFAULT NULL AFTER station_id, ADD artist VARCHAR(150) DEFAULT NULL AFTER text, ADD title VARCHAR(150) DEFAULT NULL AFTER artist');
        $this->addSql('ALTER TABLE station_media ADD text VARCHAR(150) DEFAULT NULL AFTER song_id, CHANGE title title VARCHAR(150) DEFAULT NULL, CHANGE artist artist VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE station_queue ADD text VARCHAR(150) DEFAULT NULL AFTER request_id, ADD artist VARCHAR(150) DEFAULT NULL AFTER text, ADD title VARCHAR(150) DEFAULT NULL AFTER artist');

        $this->addSql('UPDATE song_history sh JOIN songs s ON sh.song_id = s.id SET sh.text=s.text, sh.artist=s.artist, sh.title=s.title');
        $this->addSql('UPDATE station_queue sq JOIN songs s ON sq.song_id = s.id SET sq.text=s.text, sq.artist=s.artist, sq.title=s.title');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_history DROP text, DROP artist, DROP title');
        $this->addSql('ALTER TABLE station_media DROP text, CHANGE artist artist VARCHAR(200) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, CHANGE title title VARCHAR(200) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`');
        $this->addSql('ALTER TABLE station_queue DROP text, DROP artist, DROP title');
    }
}
