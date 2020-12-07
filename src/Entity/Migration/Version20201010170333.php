<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201010170333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add genre field as a first-class meta field for Station Media.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media ADD genre varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media DROP genre');
    }
}
