<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200213052842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add administrator password storage for remote relays.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_remotes ADD admin_password VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_remotes DROP admin_password');
    }
}
