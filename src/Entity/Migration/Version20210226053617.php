<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210226053617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the user login token table.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_login_tokens (id VARCHAR(16) NOT NULL, user_id INT DEFAULT NULL, created_at INT NOT NULL, verifier VARCHAR(128) NOT NULL, INDEX IDX_DDF24A16A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_login_tokens ADD CONSTRAINT FK_DDF24A16A76ED395 FOREIGN KEY (user_id) REFERENCES users (uid) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE user_login_tokens');
    }
}
