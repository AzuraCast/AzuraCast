<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201006044905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Analytics database improvements.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX search_idx ON analytics');

        $this->addSql('ALTER TABLE analytics ADD new_timestamp DATETIME(0) NOT NULL COMMENT \'(DC2Type:carbon_immutable)\', CHANGE number_avg number_avg NUMERIC(10, 2) NOT NULL, ADD number_unique INT');

        $this->addSql('UPDATE analytics SET new_timestamp=FROM_UNIXTIME(timestamp)');

        $this->addSql('ALTER TABLE analytics DROP timestamp, CHANGE new_timestamp timestamp DATETIME(0) NOT NULL COMMENT \'(DC2Type:carbon_immutable)\'');

        $this->addSql('CREATE INDEX search_idx ON analytics (type, timestamp)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX search_idx ON analytics');

        $this->addSql('ALTER TABLE analytics ADD new_timestamp INT NOT NULL');

        $this->addSql('UPDATE analytics SET new_timestamp=UNIX_TIMESTAMP(timestamp)');

        $this->addSql('ALTER TABLE analytics DROP timestamp, DROP number_unique, CHANGE number_avg number_avg INT NOT NULL, CHANGE new_timestamp timestamp INT NOT NULL');

        $this->addSql('CREATE INDEX search_idx ON analytics (type, timestamp)');
    }
}
