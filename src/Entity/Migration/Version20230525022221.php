<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230525022221 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enforce enum fields.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
                UPDATE `users` SET theme=NULL
                WHERE theme = ''
            SQL
        );

        $this->addSql(
            <<<'SQL'
                UPDATE `station_remotes` SET autodj_format=NULL
                WHERE autodj_format = ''
            SQL
        );

        $this->addSql(
            <<<'SQL'
                UPDATE `station_playlists` SET remote_type=NULL
                WHERE remote_type = ''
            SQL
        );

        $this->addSql(
            <<<'SQL'
                UPDATE `station_mounts` SET autodj_format=NULL
                WHERE autodj_format = ''
            SQL
        );

        $this->addSql(
            <<<'SQL'
                UPDATE `station_hls_streams` SET format=NULL
                WHERE format = ''
            SQL
        );

        $this->addSql(
            <<<'SQL'
                UPDATE `settings` SET ip_source=NULL
                WHERE ip_source = ''
            SQL
        );

        $this->addSql(
            <<<'SQL'
                UPDATE `settings` SET public_theme=NULL
                WHERE public_theme = ''
            SQL
        );

        $this->addSql(
            <<<'SQL'
                UPDATE `settings` SET analytics=NULL
                WHERE analytics = ''
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        // Noop
    }
}
