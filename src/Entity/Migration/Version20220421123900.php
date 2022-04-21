<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220421123900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix nullability of extended Listener columns.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listener CHANGE device_browser_family device_browser_family VARCHAR(150) DEFAULT NULL, CHANGE device_os_family device_os_family VARCHAR(150) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listener CHANGE device_browser_family device_browser_family VARCHAR(150) NOT NULL, CHANGE device_os_family device_os_family VARCHAR(150) NOT NULL');
    }
}
