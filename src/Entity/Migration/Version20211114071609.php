<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211114071609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "is_played" boolean to station queue.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE station_queue ADD is_played TINYINT(1) NOT NULL');
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->update(
            'station_queue',
            [
                'is_played' => 1,
            ],
            [
                'sent_to_autodj' => 1,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE station_queue DROP is_played');
    }
}
