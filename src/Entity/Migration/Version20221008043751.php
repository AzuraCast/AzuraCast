<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Attributes\StableMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

#[StableMigration('0.17.4')]
final class Version20221008043751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove stations that were mis-created.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            DELETE
            FROM station 
            WHERE media_storage_location_id IS NULL
              OR recordings_storage_location_id IS NULL
              OR podcasts_storage_location_id IS NULL
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        // Noop
    }
}
