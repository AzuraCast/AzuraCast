<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20261109020814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track the user who created each AzuraRelay instance.';
    }

    public function preUp(Schema $schema): void
    {
        // Clear all existing AzuraRelay instances (they're recreated at next checkin).
        $this->connection->executeQuery(
            <<<'SQL'
                DELETE FROM relays
            SQL
        );

        parent::preUp($schema);
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE relays ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE relays ADD CONSTRAINT FK_A9EEFEC1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_A9EEFEC1A76ED395 ON relays (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE relays DROP FOREIGN KEY FK_A9EEFEC1A76ED395');
        $this->addSql('DROP INDEX IDX_A9EEFEC1A76ED395 ON relays');
        $this->addSql('ALTER TABLE relays DROP user_id');
    }
}
