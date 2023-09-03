<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Attributes\StableMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

#[StableMigration('0.17.2')]
final class Version20220626171758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add HLS stream relation to listener table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listener ADD hls_stream_id INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE listener ADD CONSTRAINT FK_959C34226FE7D59F FOREIGN KEY (hls_stream_id) REFERENCES station_hls_streams (id) ON DELETE SET NULL'
        );
        $this->addSql('CREATE INDEX IDX_959C34226FE7D59F ON listener (hls_stream_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listener DROP FOREIGN KEY FK_959C34226FE7D59F');
        $this->addSql('DROP INDEX IDX_959C34226FE7D59F ON listener');
        $this->addSql('ALTER TABLE listener DROP hls_stream_id');
    }
}
