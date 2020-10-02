<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200105190343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create "sftp_user" table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sftp_user (id INT AUTO_INCREMENT NOT NULL, station_id INT DEFAULT NULL, username VARCHAR(8) NOT NULL, password VARCHAR(255) NOT NULL, public_keys LONGTEXT DEFAULT NULL, INDEX IDX_3C32EA3421BDB235 (station_id), UNIQUE INDEX username_idx (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sftp_user ADD CONSTRAINT FK_3C32EA3421BDB235 FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sftp_user');
    }
}
