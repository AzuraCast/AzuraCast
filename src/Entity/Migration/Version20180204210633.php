<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180204210633 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE api_keys');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_keys (id VARCHAR(50) NOT NULL COLLATE utf8mb4_unicode_ci, owner VARCHAR(150) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, calls_made INT NOT NULL, created INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }
}
