<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180425050351 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->changeCharset('utf8mb4_bin');
    }

    private function changeCharset(string $collate): void
    {
        $sqlLines = [
            'ALTER TABLE `station_media` DROP FOREIGN KEY FK_32AADE3AA0BDB2F3',
            'ALTER TABLE `song_history` DROP FOREIGN KEY FK_2AD16164A0BDB2F3',
            'ALTER TABLE `station_media` CONVERT TO CHARACTER SET utf8mb4 COLLATE ' . $collate,
            'ALTER TABLE `song_history` CONVERT TO CHARACTER SET utf8mb4 COLLATE ' . $collate,
            'ALTER TABLE `songs` CONVERT TO CHARACTER SET utf8mb4 COLLATE ' . $collate,
            'ALTER TABLE `song_history` ADD CONSTRAINT FK_2AD16164A0BDB2F3 FOREIGN KEY (song_id) REFERENCES songs (id) ON DELETE CASCADE',
            'ALTER TABLE `station_media` ADD CONSTRAINT FK_32AADE3AA0BDB2F3 FOREIGN KEY (song_id) REFERENCES songs (id) ON DELETE SET NULL',
        ];

        foreach ($sqlLines as $sql) {
            $this->addSql($sql);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->changeCharset('utf8mb4_unicode_ci');
    }
}
