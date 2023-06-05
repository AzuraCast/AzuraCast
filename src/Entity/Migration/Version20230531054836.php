<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230531054836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove DB-level Now Playing cache for stations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP nowplaying, DROP nowplaying_timestamp');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD nowplaying LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', ADD nowplaying_timestamp INT DEFAULT NULL');
    }
}
