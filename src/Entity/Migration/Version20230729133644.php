<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230729133644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add DB-level PSR-6 cache table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS cache_items');
        $this->addSql('CREATE TABLE cache_items (item_id VARBINARY(255) NOT NULL, item_data MEDIUMBLOB NOT NULL, item_lifetime INT UNSIGNED DEFAULT NULL, item_time INT UNSIGNED NOT NULL, PRIMARY KEY(item_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS cache_items');
    }
}
