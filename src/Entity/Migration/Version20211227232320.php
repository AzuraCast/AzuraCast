<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211227232320 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_history DROP FOREIGN KEY FK_2AD16164EA9FDD75');
        $this->addSql('ALTER TABLE song_history ADD CONSTRAINT FK_2AD16164EA9FDD75 FOREIGN KEY (media_id) REFERENCES station_media (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_history DROP FOREIGN KEY FK_2AD16164EA9FDD75');
        $this->addSql('ALTER TABLE song_history ADD CONSTRAINT FK_2AD16164EA9FDD75 FOREIGN KEY (media_id) REFERENCES station_media (id) ON DELETE CASCADE');
    }
}
