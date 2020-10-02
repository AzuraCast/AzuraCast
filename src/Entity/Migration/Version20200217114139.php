<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200217114139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ability to limit streamer connection times to their scheduled times.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_streamers ADD enforce_schedule TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_streamers DROP enforce_schedule');
    }
}
