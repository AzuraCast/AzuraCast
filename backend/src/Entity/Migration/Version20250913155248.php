<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20250913155248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add SSO tokens table for one-time login URLs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sso_tokens (comment VARCHAR(255) NOT NULL, created_at INT NOT NULL, expires_at INT NOT NULL, used TINYINT(1) NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, id VARCHAR(16) NOT NULL, verifier VARCHAR(128) NOT NULL, user_id INT NOT NULL, INDEX IDX_F0143299A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE sso_tokens ADD CONSTRAINT FK_F0143299A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sso_tokens DROP FOREIGN KEY FK_F0143299A76ED395');
        $this->addSql('DROP TABLE sso_tokens');
    }
}
