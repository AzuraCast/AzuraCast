<?php declare(strict_types = 1);

namespace Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171124184831 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE station_media_art (media_id INT NOT NULL, art LONGBLOB DEFAULT NULL, UNIQUE INDEX UNIQ_35E0CAB2EA9FDD75 (media_id), PRIMARY KEY(media_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Transfer existing art into new table.
        $this->addSql('INSERT INTO station_media_art(media_id, art) SELECT id, art FROM station_media WHERE art IS NOT NULL');

        $this->addSql('ALTER TABLE station_media DROP art');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE station_media ADD art LONGBLOB DEFAULT NULL');

        // Transfer existing art back into the main table.
        $this->addSql('UPDATE station_media AS sm, station_media_art AS sma SET sm.art = sma.art WHERE sma.media_id = sm.id');

        $this->addSql('DROP TABLE station_media_art');
    }
}
