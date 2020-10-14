<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20171128121012 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS station_media_art');
        $this->addSql('CREATE TABLE station_media_art (id INT AUTO_INCREMENT NOT NULL, media_id INT NOT NULL, art LONGBLOB DEFAULT NULL, UNIQUE INDEX UNIQ_35E0CAB2EA9FDD75 (media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE station_media_art');
    }
}
