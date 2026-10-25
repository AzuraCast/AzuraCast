<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20261017130031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add file size columns to podcast and station media.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE podcast_media ADD size BIGINT NOT NULL');
        $this->addSql('ALTER TABLE station_media ADD size BIGINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE podcast_media DROP size');
        $this->addSql('ALTER TABLE station_media DROP size');
    }
}
