<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201003023117 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Songs denormalization, part 2';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_history DROP FOREIGN KEY IF EXISTS FK_2AD16164A0BDB2F3');
        $this->addSql('DROP INDEX IF EXISTS IDX_2AD16164A0BDB2F3 ON song_history');

        $this->addSql('ALTER TABLE station_media DROP FOREIGN KEY IF EXISTS FK_32AADE3AA0BDB2F3');
        $this->addSql('DROP INDEX IF EXISTS IDX_32AADE3AA0BDB2F3 ON station_media');

        $this->addSql('ALTER TABLE station_queue DROP FOREIGN KEY IF EXISTS FK_277B0055A0BDB2F3');
        $this->addSql('DROP INDEX IF EXISTS IDX_277B0055A0BDB2F3 ON station_queue');

        $this->addSql('DROP TABLE IF EXISTS songs');

        $this->addSql('DELETE FROM station_media WHERE song_id IS NULL');
        $this->addSql('ALTER TABLE station_media CHANGE song_id song_id VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media CHANGE song_id song_id VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`');

        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE songs (id VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, text VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, artist VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, title VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created INT NOT NULL, play_count INT NOT NULL, last_played INT NOT NULL, INDEX search_idx (text, artist, title), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');

        $this->addSql('ALTER TABLE song_history ADD CONSTRAINT FK_2AD16164A0BDB2F3 FOREIGN KEY (song_id) REFERENCES songs (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_2AD16164A0BDB2F3 ON song_history (song_id)');

        $this->addSql('ALTER TABLE station_media ADD CONSTRAINT FK_32AADE3AA0BDB2F3 FOREIGN KEY (song_id) REFERENCES songs (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_32AADE3AA0BDB2F3 ON station_media (song_id)');

        $this->addSql('ALTER TABLE station_queue ADD CONSTRAINT FK_277B0055A0BDB2F3 FOREIGN KEY (song_id) REFERENCES songs (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_277B0055A0BDB2F3 ON station_queue (song_id)');
    }
}
