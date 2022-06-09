<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220608113502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ACME settings to Settings table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE settings ADD acme_email VARCHAR(255) DEFAULT NULL, ADD acme_domains VARCHAR(255) DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings DROP acme_email, DROP acme_domains');
    }
}
