<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220610125828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Re-align genre field.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media CHANGE genre genre VARCHAR(30) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE station_media CHANGE genre genre VARCHAR(30) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`'
        );
    }
}
