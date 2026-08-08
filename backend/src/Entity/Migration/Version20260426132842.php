<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260426132842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD requests_only_via_playlists TINYINT NOT NULL');
        $this->addSql('ALTER TABLE station_schedules ADD prevent_requests TINYINT NOT NULL');


        $rows = $this->connection->iterateAssociative(
            <<<'SQL'
                SELECT id, backend_options
                FROM station_playlists
                WHERE FIND_IN_SET('prioritize', backend_options)
            SQL
        );

        foreach ($rows as $playlist) {
            $backendOptions = array_values(array_filter(
                explode(',', $playlist['backend_options']),
                fn(string $option): bool => 'prioritize' !== $option
            ));

            $this->connection->update('station_playlists', [
                'backend_options' => implode(',', $backendOptions),
            ], [
                'id' => $playlist['id'],
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP requests_only_via_playlists');
        $this->addSql('ALTER TABLE station_schedules DROP prevent_requests');
    }
}
