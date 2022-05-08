<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211227232320 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change on-delete behavior of media on song_history table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song_history DROP FOREIGN KEY FK_2AD16164EA9FDD75');
        $this->addSql('ALTER TABLE song_history ADD CONSTRAINT FK_2AD16164EA9FDD75 FOREIGN KEY (media_id) REFERENCES station_media (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song_history DROP FOREIGN KEY FK_2AD16164EA9FDD75');
        $this->addSql('ALTER TABLE song_history ADD CONSTRAINT FK_2AD16164EA9FDD75 FOREIGN KEY (media_id) REFERENCES station_media (id) ON DELETE CASCADE');
    }
}
