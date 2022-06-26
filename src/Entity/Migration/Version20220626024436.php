<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220626024436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add listeners to HLS streams.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_hls_streams ADD listeners INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_hls_streams DROP listeners');
    }
}
