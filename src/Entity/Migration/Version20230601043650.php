<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230601043650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove serialized NowPlaying field for AzuraRelays.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE relays DROP nowplaying');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE relays ADD nowplaying LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
    }
}
