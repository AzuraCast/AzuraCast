<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add request threshold to Station entity.
 */
final class Version20170414205418 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD request_threshold INT DEFAULT NULL');
    }

    public function postup(Schema $schema): void
    {
        $this->connection->update('station', [
            'request_threshold' => 15,
        ], [
            'enable_requests' => 1,
        ]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP request_threshold');
    }
}
