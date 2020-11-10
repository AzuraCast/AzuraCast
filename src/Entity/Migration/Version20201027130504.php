<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201027130504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Song storage consolidation, part 2.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media ADD CONSTRAINT FK_32AADE3ACDDD8AF FOREIGN KEY (storage_location_id) REFERENCES storage_location (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_32AADE3ACDDD8AF ON station_media (storage_location_id)');
        $this->addSql('CREATE UNIQUE INDEX path_unique_idx ON station_media (path, storage_location_id)');

        $this->addSql('ALTER TABLE station DROP radio_media_dir, DROP storage_quota, DROP storage_used');

        $this->addSql('ALTER TABLE station_media DROP station_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD radio_media_dir VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, ADD storage_quota BIGINT DEFAULT NULL, ADD storage_used BIGINT DEFAULT NULL');

        $this->addSql('ALTER TABLE station_media ADD station_id INT NOT NULL');

        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_32AADE3ACDDD8AF');
        $this->addSql('DROP INDEX IDX_32AADE3ACDDD8AF ON station_media');
        $this->addSql('DROP INDEX path_unique_idx ON station_media');
    }
}
