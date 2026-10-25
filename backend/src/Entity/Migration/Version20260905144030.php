<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260905144030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add queue reset options to schedule entries.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE station_schedules ADD reset_queue_at_start TINYINT NOT NULL, ADD reset_queue_recursive TINYINT NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_schedules DROP reset_queue_at_start, DROP reset_queue_recursive');
    }
}
