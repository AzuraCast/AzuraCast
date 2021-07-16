<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20161122035237 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX path_unique_idx ON station_media');
        $this->addSql('CREATE UNIQUE INDEX path_unique_idx ON station_media (path, station_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX path_unique_idx ON station_media');
        $this->addSql('CREATE UNIQUE INDEX path_unique_idx ON station_media (path)');
    }
}
