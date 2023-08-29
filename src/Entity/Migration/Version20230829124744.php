<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230829124744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add S3 "Use path style" boolean.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE storage_location ADD s3_use_path_style TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE storage_location DROP s3_use_path_style');
    }
}
