<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260908090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Keep existing stations resetting sequential playlist queues on restart.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
                UPDATE station
                SET backend_config = JSON_SET(
                    COALESCE(backend_config, '{}'),
                    '$.reset_sequential_queues_on_restart',
                    TRUE
                )
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
                UPDATE station
                SET backend_config = JSON_REMOVE(backend_config, '$.reset_sequential_queues_on_restart')
            SQL
        );
    }
}
