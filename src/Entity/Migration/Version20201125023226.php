<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201125023226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix the "text" attribute of station media.';
    }

    public function up(Schema $schema): void
    {
        // Delete all non-processed media entries
        $this->addSql('
            DELETE FROM station_media
            WHERE artist IS NULL OR title IS NULL
        ');

        $this->addSql('
            UPDATE station_media
            SET text=SUBSTRING(CONCAT(artist, \' - \', title), 1, 150),
                song_id=MD5(LOWER(REPLACE(REPLACE(CONCAT(artist,\' - \',title),\'-\',\'\'),\' \',\'\')))
        ');
    }

    public function down(Schema $schema): void
    {
        // No-op
    }
}
