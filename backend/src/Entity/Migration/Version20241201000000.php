<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add simulcasting table
 */
final class Version20241201000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add simulcasting table for managing simulcasting streams';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE station_simulcasting (
            id INT AUTO_INCREMENT NOT NULL,
            station_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            adapter VARCHAR(50) NOT NULL,
            stream_key VARCHAR(500) NOT NULL,
            status VARCHAR(20) NOT NULL,
            error_message LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_STATION_SIMULCASTING_STATION (station_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE station_simulcasting ADD CONSTRAINT FK_STATION_SIMULCASTING_STATION 
            FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_simulcasting DROP FOREIGN KEY FK_STATION_SIMULCASTING_STATION');
        $this->addSql('DROP TABLE station_simulcasting');
    }
}

