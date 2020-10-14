<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180415235105 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE station_media_custom_field (id INT AUTO_INCREMENT NOT NULL, media_id INT NOT NULL, field_id INT NOT NULL, field_value VARCHAR(255) DEFAULT NULL, INDEX IDX_35DC02AAEA9FDD75 (media_id), INDEX IDX_35DC02AA443707B0 (field_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE custom_field (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE station_media_custom_field ADD CONSTRAINT FK_35DC02AAEA9FDD75 FOREIGN KEY (media_id) REFERENCES station_media (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_media_custom_field ADD CONSTRAINT FK_35DC02AA443707B0 FOREIGN KEY (field_id) REFERENCES custom_field (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media_custom_field DROP FOREIGN KEY FK_35DC02AA443707B0');
        $this->addSql('DROP TABLE station_media_custom_field');
        $this->addSql('DROP TABLE custom_field');
    }
}
