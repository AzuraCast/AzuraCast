<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230428062001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "IP Source" setting.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings ADD ip_source VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings DROP ip_source');
    }
}
