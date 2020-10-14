<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190810234058 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_mounts ADD listeners_unique INT NOT NULL, ADD listeners_total INT NOT NULL');
        $this->addSql('ALTER TABLE station_remotes ADD listeners_unique INT NOT NULL, ADD listeners_total INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_mounts DROP listeners_unique, DROP listeners_total');
        $this->addSql('ALTER TABLE station_remotes DROP listeners_unique, DROP listeners_total');
    }
}
