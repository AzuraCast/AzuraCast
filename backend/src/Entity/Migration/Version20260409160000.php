<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260409160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace allow_requests boolean with request_mode enum column on clockwheel children.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE station_playlist_child "
            . "ADD request_mode VARCHAR(20) NOT NULL DEFAULT 'none'"
        );

        $this->addSql(
            "UPDATE station_playlist_child "
            . "SET request_mode = 'any' WHERE allow_requests = 1"
        );

        $this->addSql(
            'ALTER TABLE station_playlist_child DROP allow_requests'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE station_playlist_child '
            . 'ADD allow_requests TINYINT(1) NOT NULL DEFAULT 0'
        );

        $this->addSql(
            "UPDATE station_playlist_child "
            . "SET allow_requests = 1 WHERE request_mode = 'any'"
        );

        $this->addSql(
            'ALTER TABLE station_playlist_child DROP request_mode'
        );
    }
}
