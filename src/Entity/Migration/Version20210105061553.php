<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210105061553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a separate setting for showing/hiding the "Download" button in On-Demand streaming.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD enable_on_demand_download TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP enable_on_demand_download');
    }
}
