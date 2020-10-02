<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200124183957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "amplify" metadata to associate with station media.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media ADD amplify NUMERIC(3, 1) DEFAULT NULL AFTER mtime');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media DROP amplify');
    }
}
