<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260605120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add custom_domain field to station for per-station public page domains.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD custom_domain VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP COLUMN custom_domain');
    }
}
