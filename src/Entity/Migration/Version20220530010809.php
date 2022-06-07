<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220530010809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add artwork file to StationStreamer';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_streamers ADD art_updated_at INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_streamers DROP art_updated_at');
    }
}
