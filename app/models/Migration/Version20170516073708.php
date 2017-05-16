<?php

namespace Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170516073708 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE listener (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, listener_uid INT NOT NULL, listener_ip VARCHAR(45) NOT NULL, listener_user_agent VARCHAR(255) NOT NULL, timestamp_start INT NOT NULL, timestamp_end INT NOT NULL, INDEX IDX_959C342221BDB235 (station_id), INDEX update_idx (listener_uid, listener_ip), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE listener ADD CONSTRAINT FK_959C342221BDB235 FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_history ADD unique_listeners SMALLINT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE listener');
        $this->addSql('ALTER TABLE song_history DROP unique_listeners');
    }
}
