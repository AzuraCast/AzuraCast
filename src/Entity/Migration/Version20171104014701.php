<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20171104014701 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media ADD unique_id VARCHAR(25) DEFAULT NULL');
    }

    public function postup(Schema $schema): void
    {
        foreach ($this->connection->fetchAllAssociative('SELECT * FROM station_media') as $record) {
            $this->connection->update(
                'station_media',
                [
                    'unique_id' => bin2hex(random_bytes(12)),
                ],
                [
                    'id' => $record['id'],
                ]
            );
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media DROP unique_id');
    }
}
