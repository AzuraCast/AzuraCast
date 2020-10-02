<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191101075303 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists DROP schedule_start_time, DROP schedule_end_time, DROP schedule_days');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists ADD schedule_start_time SMALLINT NOT NULL, ADD schedule_end_time SMALLINT NOT NULL, ADD schedule_days VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_general_ci');
    }
}
