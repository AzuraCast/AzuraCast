<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230829093303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand station media genre field.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media CHANGE genre genre VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media CHANGE genre genre VARCHAR(30) DEFAULT NULL');
    }
}
