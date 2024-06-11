<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240519163701 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand media field lengths.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song_history CHANGE text text VARCHAR(512) DEFAULT NULL, CHANGE artist artist VARCHAR(255) DEFAULT NULL, CHANGE title title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE station_media CHANGE title title VARCHAR(255) DEFAULT NULL, CHANGE artist artist VARCHAR(255) DEFAULT NULL, CHANGE text text VARCHAR(512) DEFAULT NULL');
        $this->addSql('ALTER TABLE station_queue CHANGE text text VARCHAR(512) DEFAULT NULL, CHANGE artist artist VARCHAR(255) DEFAULT NULL, CHANGE title title VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media CHANGE text text VARCHAR(303) DEFAULT NULL, CHANGE artist artist VARCHAR(150) DEFAULT NULL, CHANGE title title VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE station_queue CHANGE text text VARCHAR(303) DEFAULT NULL, CHANGE artist artist VARCHAR(150) DEFAULT NULL, CHANGE title title VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE song_history CHANGE text text VARCHAR(303) DEFAULT NULL, CHANGE artist artist VARCHAR(150) DEFAULT NULL, CHANGE title title VARCHAR(150) DEFAULT NULL');
    }
}
