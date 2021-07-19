<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20171022005913 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_mounts ADD remote_type VARCHAR(50) DEFAULT NULL, ADD remote_url VARCHAR(255) DEFAULT NULL, ADD remote_mount VARCHAR(150) DEFAULT NULL, ADD remote_source_username VARCHAR(100) DEFAULT NULL, ADD remote_source_password VARCHAR(100) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_mounts DROP remote_type, DROP remote_url, DROP remote_mount, DROP remote_source_username, DROP remote_source_password');
    }
}
