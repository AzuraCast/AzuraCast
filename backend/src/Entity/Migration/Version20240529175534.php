<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Attributes\StableMigration;
use Doctrine\DBAL\Schema\Schema;

#[
    StableMigration('0.20.1')
]
final class Version20240529175534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert "fade_overlap" back to "fade_start_next".';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media ADD fade_start_next NUMERIC(6, 1) DEFAULT NULL AFTER amplify');

        $this->addSql(
            <<<'SQL'
                UPDATE station_media
                    SET fade_start_next=IF(cue_out IS NOT NULL, cue_out, length) - fade_overlap
                    WHERE fade_overlap IS NOT NULL
            SQL
        );

        $this->addSql('ALTER TABLE station_media DROP fade_overlap');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media ADD fade_overlap NUMERIC(6, 1) DEFAULT NULL');
        $this->addSql('ALTER TABLE station_media DROP fade_start_next');
    }
}
