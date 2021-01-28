<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201208185538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Dropbox support to Storage Locations.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_keys CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE storage_location ADD IF NOT EXISTS dropbox_auth_token VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_keys CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE storage_location DROP dropbox_auth_token');
    }
}
