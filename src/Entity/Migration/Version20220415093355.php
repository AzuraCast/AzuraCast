<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220415093355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add device and location metadata to Listeners table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE listener ADD location_description VARCHAR(255) NOT NULL, ADD location_region VARCHAR(150) DEFAULT NULL, ADD location_city VARCHAR(150) DEFAULT NULL, ADD location_country VARCHAR(2) DEFAULT NULL, ADD location_lat NUMERIC(10, 6) DEFAULT NULL, ADD location_lon NUMERIC(10, 6) DEFAULT NULL, ADD device_client VARCHAR(255) NOT NULL, ADD device_is_browser TINYINT(1) NOT NULL, ADD device_is_mobile TINYINT(1) NOT NULL, ADD device_is_bot TINYINT(1) NOT NULL, ADD device_browser_family VARCHAR(150) NOT NULL, ADD device_os_family VARCHAR(150) NOT NULL'
        );
        $this->addSql('CREATE INDEX idx_statistics_country ON listener (location_country)');
        $this->addSql('CREATE INDEX idx_statistics_os ON listener (device_os_family)');
        $this->addSql('CREATE INDEX idx_statistics_browser ON listener (device_browser_family)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_statistics_country ON listener');
        $this->addSql('DROP INDEX idx_statistics_os ON listener');
        $this->addSql('DROP INDEX idx_statistics_browser ON listener');
        $this->addSql(
            'ALTER TABLE listener DROP location_description, DROP location_region, DROP location_city, DROP location_country, DROP location_lat, DROP location_lon, DROP device_client, DROP device_is_browser, DROP device_is_mobile, DROP device_is_bot, DROP device_browser_family, DROP device_os_family'
        );
    }
}
