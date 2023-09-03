<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Attributes\StableMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

#[
    StableMigration('0.18.1'),
    StableMigration('0.18.0')
]
final class Version20230410210554 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new Dropbox-related keys.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE storage_location ADD dropbox_app_key VARCHAR(50) DEFAULT NULL, ADD dropbox_app_secret VARCHAR(150) DEFAULT NULL, ADD dropbox_refresh_token VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE storage_location DROP dropbox_app_key, DROP dropbox_app_secret, DROP dropbox_refresh_token');
    }
}
