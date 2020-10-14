<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180206105454 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_keys (id VARCHAR(16) NOT NULL, user_id INT NOT NULL, verifier VARCHAR(128) NOT NULL, comment VARCHAR(255) DEFAULT NULL, INDEX IDX_9579321FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE api_keys ADD CONSTRAINT FK_9579321FA76ED395 FOREIGN KEY (user_id) REFERENCES users (uid) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE api_keys');
    }
}
