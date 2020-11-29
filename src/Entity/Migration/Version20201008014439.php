<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201008014439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clean up station_media table from songs migration.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM station_media WHERE song_id IS NULL');
        $this->addSql('UPDATE station_media SET text=SUBSTRING(CONCAT(artist, \' - \', title), 1, 150) WHERE text IS NULL OR text = \'\'');
    }

    public function down(Schema $schema): void
    {
        // No-op
    }
}
