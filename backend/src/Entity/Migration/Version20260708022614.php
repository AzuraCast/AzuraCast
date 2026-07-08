<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260708022614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add global webhooks setting toggle.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings ADD enable_all_webhooks TINYINT NOT NULL');
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->update(
            'settings',
            [
                'enable_all_webhooks' => 1,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings DROP enable_all_webhooks');
    }

}
