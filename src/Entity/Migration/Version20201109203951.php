<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201109203951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique constraint for analytics.';
    }

    public function preUp(Schema $schema): void
    {
        $analytics = $this->connection->fetchAllAssociative('SELECT id, station_id, type, moment FROM analytics ORDER BY id ASC');
        $rows = [];

        foreach ($analytics as $row) {
            $rowKey = ($row['station_id'] ?? 'all') . '_' . $row['type'] . '_' . $row['moment'];

            if (isset($rows[$rowKey])) {
                $this->connection->delete('analytics', [
                    'id' => $row['id'],
                ], [
                    'id' => ParameterType::INTEGER,
                ]);
            } else {
                $rows[$rowKey] = $row['id'];
            }
        }
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX stats_unique_idx ON analytics (station_id, type, moment)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX stats_unique_idx ON analytics');
    }
}
