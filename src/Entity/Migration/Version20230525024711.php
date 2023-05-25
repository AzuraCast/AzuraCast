<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230525024711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix frontend/backend type on stations being nullable.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            UPDATE `station`
            SET frontend_type='icecast'
            WHERE frontend_type IS NULL
            SQL
        );

        $this->addSql(
            <<<'SQL'
            UPDATE `station`
            SET backend_type='liquidsoap'
            WHERE backend_type IS NULL
            SQL
        );

        $this->addSql(
            <<<'SQL'
            ALTER TABLE `station` 
                CHANGE frontend_type frontend_type VARCHAR(100) NOT NULL, 
                CHANGE backend_type backend_type VARCHAR(100) NOT NULL
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            ALTER TABLE station 
                CHANGE frontend_type frontend_type VARCHAR(100) DEFAULT NULL, 
                CHANGE backend_type backend_type VARCHAR(100) DEFAULT NULL
            SQL
        );
    }
}
