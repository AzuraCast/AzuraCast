<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210528211201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename "users" table identifier to "id".';
    }

    public function up(Schema $schema): void
    {
        // Automatically renames all foreign key constraints too. Handy!
        $this->addSql('ALTER TABLE users RENAME COLUMN uid TO id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users RENAME COLUMN id TO uid');
    }
}
