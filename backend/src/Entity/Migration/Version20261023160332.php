<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20261023160332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add computed AutoCue column to station media.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media ADD computed_auto_cue JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media DROP computed_auto_cue');
    }
}
