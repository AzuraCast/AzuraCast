<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200503005148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add per-station default album art.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD default_album_art_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP default_album_art_url');
    }
}
