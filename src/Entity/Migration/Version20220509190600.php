<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220509190600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Stereo Tool configuration path to Stations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD stereo_tool_configuration_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP stereo_tool_configuration_path');
    }
}
