<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220603065416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add HLS fields.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE station_hls_streams (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, name VARCHAR(100) NOT NULL, format VARCHAR(10) DEFAULT NULL, bitrate SMALLINT DEFAULT NULL, INDEX IDX_9ECC9CD021BDB235 (station_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE station_hls_streams ADD CONSTRAINT FK_9ECC9CD021BDB235 FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE'
        );
        $this->addSql('ALTER TABLE station ADD enable_hls TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE station_hls_streams');
        $this->addSql('ALTER TABLE station DROP enable_hls');
    }
}
