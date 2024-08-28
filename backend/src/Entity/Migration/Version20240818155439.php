<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240818155439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add request_priority field to station.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD request_priority SMALLINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP request_priority');
    }
}
