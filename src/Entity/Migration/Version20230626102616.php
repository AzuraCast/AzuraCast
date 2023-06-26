<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230626102616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store theme at the browser level, not the user level.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP theme');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD theme VARCHAR(25) DEFAULT NULL');
    }
}
