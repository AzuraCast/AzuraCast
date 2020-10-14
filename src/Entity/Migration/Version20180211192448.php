<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180211192448 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE station_webhooks (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, name VARCHAR(100) NOT NULL, is_enabled TINYINT(1) NOT NULL, triggers LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', config LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', INDEX IDX_1516958B21BDB235 (station_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE station_webhooks ADD CONSTRAINT FK_1516958B21BDB235 FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE station_webhooks');
    }
}
