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
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            UPDATE station_media
            SET text=CONCAT(artist,\' - \',title),
                song_id=MD5(LOWER(REPLACE(REPLACE(CONCAT(artist,\' - \',title),\'-\',\'\'),\' \',\'\')))
            WHERE artist != \'\' OR title != \'\'
        ');
    }

    public function down(Schema $schema): void
    {
        // No-op
    }
}
