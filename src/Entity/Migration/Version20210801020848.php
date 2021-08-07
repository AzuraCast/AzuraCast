<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210801020848 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add intro file support to station mounts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_mounts ADD intro_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_mounts DROP intro_path');
    }
}
