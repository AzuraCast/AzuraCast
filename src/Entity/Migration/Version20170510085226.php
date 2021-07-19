<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170510085226 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP radio_base_dir');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD radio_base_dir VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci');
    }

    public function postdown(Schema $schema): void
    {
        foreach ($this->connection->fetchAllAssociative('SELECT * FROM station') as $station) {
            $this->connection->update(
                'station',
                [
                    'radio_base_dir' => str_replace('/media', '', $station['radio_media_dir']),
                ],
                [
                    'id' => $station['id'],
                ]
            );
        }
    }
}
