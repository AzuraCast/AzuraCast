<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201222063647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create a table for tracking unprocessed media.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE unprocessable_media (id INT AUTO_INCREMENT NOT NULL, storage_location_id INT NOT NULL, path VARCHAR(500) DEFAULT NULL, mtime INT DEFAULT NULL, error LONGTEXT DEFAULT NULL, INDEX IDX_DCB6B9EDCDDD8AF (storage_location_id), UNIQUE INDEX path_unique_idx (path, storage_location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE unprocessable_media ADD CONSTRAINT FK_DCB6B9EDCDDD8AF FOREIGN KEY (storage_location_id) REFERENCES storage_location (id) ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE unprocessable_media');
    }
}
