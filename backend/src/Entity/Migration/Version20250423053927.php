<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20250423053927 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove the "enable_advanced_features" setting.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE settings DROP enable_advanced_features
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE settings ADD enable_advanced_features TINYINT(1) NOT NULL
        SQL);
    }
}
