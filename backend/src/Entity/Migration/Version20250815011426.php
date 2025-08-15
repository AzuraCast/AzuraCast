<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20250815011426 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add star rating and favorite fields to station media.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media ADD rating SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE station_media ADD is_favorite TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media DROP rating');
        $this->addSql('ALTER TABLE station_media DROP is_favorite');
    }
}
