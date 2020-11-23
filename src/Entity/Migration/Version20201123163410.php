<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201123163410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Addition of hosting features';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS `packages`(
            `id` int(11) NOT NULL AUTO_INCREMENT, 
            `user_id` int(11) unsigned NOT NULL,
            `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
            `is_enabled` TINYINT(1) NOT NULL DEFAULT 0,
            `bitrate` integer NOT NULL DEFAULT 320,
            `max_listeners` integer NOT NULL DEFAULT 0,
            `frontend_type` varchar(100) COLLATE  utf8_unicode_ci NOT NULL,
            PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

        // add reseller fields to users table
        $this->addSql("ALTER TABLE users ADD reseller_id int(11) unsigned DEFAULT NULL, ADD is_reseller TINYINT(1) NOT NULL DEFAULT 0;");

        // add package and user fields to stations table
        $this->addSql("ALTER TABLE station ADD user_id int(11) unsigned DEFAULT NULL, ADD package_id int(11) unsigned DEFAULT NULL;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE station DROP user_id, DROP package_id;");
        $this->addSql("ALTER TABLE users DROP reseller_id, DROP is_reseller;");
        $this->addSql("DROP TABLE packages;");
    }
}
