<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240822165006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add requests_follow_format field to station';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD requests_follow_format TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP requests_follow_format');
    }
}
