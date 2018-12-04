<?php declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181202180617 extends AbstractMigration
{
    public function preUp(Schema $schema)
    {
        $stations = $this->connection->fetchAll('SELECT s.* FROM station AS s');

        foreach($stations as $station) {
            $base_dir = $station['radio_base_dir'];
            $art_dir = $base_dir.'/album_art';
            mkdir($art_dir, 0777);

            $art_raw = $this->connection->fetchAll('SELECT sm.unique_id, sma.art FROM station_media AS sm JOIN station_media_art sma on sm.id = sma.media_id');

            foreach($art_raw as $art_row) {
                $art_path = $art_dir.'/'.$art_row['unique_id'].'.jpg';
                file_put_contents($art_path, $art_row['art']);
            }
        }
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE station_media_art');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE station_media_art (id INT AUTO_INCREMENT NOT NULL, media_id INT NOT NULL, art LONGBLOB DEFAULT NULL, UNIQUE INDEX UNIQ_35E0CAB2EA9FDD75 (media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE station_media_art ADD CONSTRAINT FK_35E0CAB2EA9FDD75 FOREIGN KEY (media_id) REFERENCES station_media (id) ON DELETE CASCADE');
    }
}
