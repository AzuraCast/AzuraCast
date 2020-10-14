<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Make media length an INT instead of SMALLINT for songs longer than 9 hours (!)
 */
final class Version20180716185805 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media CHANGE length length INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media CHANGE length length SMALLINT NOT NULL');
    }
}
