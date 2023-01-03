<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230102192033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Station branding config column, part 1.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD branding_config LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function postUp(Schema $schema): void
    {
        $allStations = $this->connection->fetchAllAssociative(
            <<<'SQL'
            SELECT id, default_album_art_url 
            FROM station 
            WHERE default_album_art_url IS NOT NULL
            SQL
        );

        foreach ($allStations as $station) {
            $this->connection->update(
                'station',
                [
                    'branding_config' => json_encode([
                        'default_album_art_url' => $station['default_album_art_url'],
                    ], JSON_THROW_ON_ERROR),
                ],
                [
                    'id' => $station['id'],
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP branding_config');
    }
}
