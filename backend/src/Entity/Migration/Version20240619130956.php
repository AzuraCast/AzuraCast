<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240619130956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate media metadata to an expandable field, pt. 1';
    }

    public function up(Schema $schema): void
    {
        // Clean up records that won't comply with new field changes.
        $this->addSql(<<<'SQL'
            DELETE FROM station_media
            WHERE unique_id IS NULL OR mtime IS NULL OR length IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE station_media 
                ADD extra_metadata JSON DEFAULT NULL, 
                DROP length_text,
                CHANGE length length NUMERIC(7, 2) NOT NULL, 
                CHANGE mtime mtime INT NOT NULL, 
                CHANGE unique_id unique_id VARCHAR(25) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE station_media 
                ADD length_text VARCHAR(10) DEFAULT NULL,
                DROP extra_metadata, 
                CHANGE unique_id unique_id VARCHAR(25) DEFAULT NULL, 
                CHANGE length length NUMERIC(7, 2) DEFAULT NULL, 
                CHANGE mtime mtime INT DEFAULT NULL
        SQL);
    }
}
