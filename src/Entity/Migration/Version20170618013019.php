<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170618013019 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song_history ADD media_id INT DEFAULT NULL, ADD duration INT DEFAULT NULL');
        $this->addSql('ALTER TABLE song_history ADD CONSTRAINT FK_2AD16164EA9FDD75 FOREIGN KEY (media_id) REFERENCES station_media (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_2AD16164EA9FDD75 ON song_history (media_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song_history DROP FOREIGN KEY FK_2AD16164EA9FDD75');
        $this->addSql('DROP INDEX IDX_2AD16164EA9FDD75 ON song_history');
        $this->addSql('ALTER TABLE song_history DROP media_id, DROP duration');
    }
}
