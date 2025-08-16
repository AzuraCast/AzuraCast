<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20250816075539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename semicolons in playlist names.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            UPDATE station_playlists
            SET name=REPLACE(name, ';', ':')
            WHERE name LIKE :string
            SQL,
            [
                'string' => '%;%',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        // Noop
    }
}
