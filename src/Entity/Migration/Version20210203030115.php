<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210203030115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate advanced features setting to database.';
    }

    public function up(Schema $schema): void
    {
        // No-op
        $this->addSql('SELECT 1');
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->delete(
            'settings',
            [
                'setting_key' => 'enableAdvancedFeatures',
            ]
        );

        $this->connection->insert(
            'settings',
            [
                'setting_key' => 'enableAdvancedFeatures',
                'setting_value' => 'true',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        // No-op
        $this->addSql('SELECT 1');
    }
}
