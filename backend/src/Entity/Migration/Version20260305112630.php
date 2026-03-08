<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Attributes\StableMigration;
use Doctrine\DBAL\Schema\Schema;

#[StableMigration('0.23.4')]
final class Version20260305112630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "enable_public_api" to station and "enable_liquidsoap_editing" to settings.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings ADD enable_liquidsoap_editing TINYINT NOT NULL AFTER api_access_control');
        $this->addSql('ALTER TABLE station ADD enable_public_api TINYINT NOT NULL AFTER enable_public_page');
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->update(
            'settings',
            [
                'enable_liquidsoap_editing' => 1,
            ]
        );

        $this->connection->executeQuery(
            <<<'SQL'
                UPDATE station SET enable_public_api=enable_public_page
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings DROP enable_liquidsoap_editing');
        $this->addSql('ALTER TABLE station DROP enable_public_api');
    }
}
