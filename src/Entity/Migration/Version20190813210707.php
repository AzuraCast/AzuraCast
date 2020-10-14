<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190813210707 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, timestamp INT NOT NULL, operation SMALLINT NOT NULL, class VARCHAR(255) NOT NULL, identifier VARCHAR(255) NOT NULL, target_class VARCHAR(255) DEFAULT NULL, target VARCHAR(255) DEFAULT NULL, changes LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', user VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE audit_log');
    }
}
