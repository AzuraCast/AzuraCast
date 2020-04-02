<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200402212036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make SongHistory-to-StationRequest a many-to-one relationship.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE song_history DROP INDEX UNIQ_2AD16164427EB8A5, ADD INDEX IDX_2AD16164427EB8A5 (request_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE song_history DROP INDEX IDX_2AD16164427EB8A5, ADD UNIQUE INDEX UNIQ_2AD16164427EB8A5 (request_id)');
    }
}
