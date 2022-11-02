<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221102125558 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user-level 24-hour time setting.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD show_24_hour_time TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP show_24_hour_time');
    }
}
