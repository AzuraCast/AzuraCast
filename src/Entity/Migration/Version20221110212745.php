<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221110212745 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate from NChan to static JSON for NP.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings CHANGE enable_websockets enable_static_nowplaying TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings CHANGE enable_static_nowplaying enable_websockets TINYINT(1) NOT NULL');
    }
}
