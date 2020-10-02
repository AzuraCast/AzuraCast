<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200321174535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "skip_delay" field to station requests.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_requests ADD skip_delay TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_requests DROP skip_delay');
    }
}
