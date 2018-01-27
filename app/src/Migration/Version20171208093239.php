<?php declare(strict_types = 1);

namespace Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171208093239 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE station ADD short_name VARCHAR(100) DEFAULT NULL');
    }

    public function postUp(Schema $schema)
    {
        $all_records = $this->connection->fetchAll("SELECT * FROM station");

        foreach ($all_records as $record) {
            $this->connection->update('station', [
                'short_name' => \Entity\Station::getStationShortName($record['name']),
            ], [
                'id' => $record['id'],
            ]);
        }
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE station DROP short_name');
    }
}
