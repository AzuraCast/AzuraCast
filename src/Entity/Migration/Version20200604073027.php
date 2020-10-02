<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200604073027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extend size of the "weight" parameter.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlist_media CHANGE weight weight INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlist_media CHANGE weight weight SMALLINT NOT NULL');
    }
}
