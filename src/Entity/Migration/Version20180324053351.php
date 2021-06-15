<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180324053351 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD is_enabled TINYINT(1) NOT NULL');
    }

    public function postup(Schema $schema): void
    {
        $this->connection->executeStatement('UPDATE station SET is_enabled=1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP is_enabled');
    }
}
