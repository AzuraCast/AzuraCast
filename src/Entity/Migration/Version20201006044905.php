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
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE analytics ADD new_timestamp DATETIME(0) NOT NULL COMMENT \'(DC2Type:carbon_immutable)\', ADD number_unique INT');

        $this->addSql('UPDATE analytics SET new_timestamp=FROM_UNIXTIME(timestamp)');

        $this->addSql('ALTER TABLE analytics DROP timestamp, CHANGE new_timestamp timestamp DATETIME(0) NOT NULL COMMENT \'(DC2Type:carbon_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE analytics ADD new_timestamp INT NOT NULL');

        $this->addSql('UPDATE analytics SET new_timestamp=UNIX_TIMESTAMP(timestamp)');

        $this->addSql('ALTER TABLE analytics DROP timestamp, DROP number_unique, CHANGE new_timestamp timestamp INT NOT NULL');
    }
}
