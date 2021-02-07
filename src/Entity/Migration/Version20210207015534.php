<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210207015534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand smallint fields in "song_history" to full integers.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE song_history CHANGE listeners_end listeners_end INT DEFAULT NULL, CHANGE delta_total delta_total INT NOT NULL, CHANGE delta_positive delta_positive INT NOT NULL, CHANGE delta_negative delta_negative INT NOT NULL, CHANGE unique_listeners unique_listeners INT DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE song_history CHANGE listeners_end listeners_end SMALLINT DEFAULT NULL, CHANGE unique_listeners unique_listeners SMALLINT DEFAULT NULL, CHANGE delta_total delta_total SMALLINT NOT NULL, CHANGE delta_positive delta_positive SMALLINT NOT NULL, CHANGE delta_negative delta_negative SMALLINT NOT NULL'
        );
    }
}
