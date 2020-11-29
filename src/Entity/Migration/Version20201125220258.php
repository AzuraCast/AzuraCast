<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201125220258 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand the length of the "text" field.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_history CHANGE text text VARCHAR(303) DEFAULT NULL');
        $this->addSql('ALTER TABLE station_media CHANGE text text VARCHAR(303) DEFAULT NULL');
        $this->addSql('ALTER TABLE station_queue CHANGE text text VARCHAR(303) DEFAULT NULL');
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->executeQuery('UPDATE station_media SET text=CONCAT(artist, \' - \', title)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_history CHANGE text text VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`');
        $this->addSql('ALTER TABLE station_media CHANGE text text VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`');
        $this->addSql('ALTER TABLE station_queue CHANGE text text VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`');
    }
}
