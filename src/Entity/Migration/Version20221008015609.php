<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221008015609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Support App Key and App Secret for Dropbox Storage Locations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE storage_location ADD dropbox_app_key VARCHAR(255) DEFAULT NULL, ADD dropbox_app_secret VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE storage_location DROP dropbox_app_key, DROP dropbox_app_secret');
    }
}
