<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Station;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20171208093239 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD short_name VARCHAR(100) DEFAULT NULL');
    }

    public function postup(Schema $schema): void
    {
        $all_records = $this->connection->fetchAll('SELECT * FROM station');

        foreach ($all_records as $record) {
            $this->connection->update('station', [
                'short_name' => Station::getStationShortName($record['name']),
            ], [
                'id' => $record['id'],
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP short_name');
    }
}
