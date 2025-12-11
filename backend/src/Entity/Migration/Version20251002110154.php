<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Attributes\StableMigration;
use Doctrine\DBAL\Schema\Schema;

#[StableMigration('0.23.2')]
final class Version20251002110154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand user_login_tokens to support multiple types.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM user_login_tokens');

        $this->addSql('ALTER TABLE user_login_tokens ADD type VARCHAR(50) NOT NULL, ADD comment VARCHAR(255) DEFAULT NULL, ADD expires_at INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_login_tokens DROP type, DROP comment, DROP expires_at');
    }
}
