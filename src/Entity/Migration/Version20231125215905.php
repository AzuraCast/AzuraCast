<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231125215905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add table for user passkeys.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_passkeys (id VARCHAR(64) NOT NULL, user_id INT NOT NULL, created_at INT NOT NULL, name VARCHAR(255) NOT NULL, full_id LONGTEXT NOT NULL, public_key_pem LONGTEXT NOT NULL, INDEX IDX_A2309328A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_passkeys ADD CONSTRAINT FK_A2309328A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_passkeys DROP FOREIGN KEY FK_A2309328A76ED395');
        $this->addSql('DROP TABLE user_passkeys');
    }
}
