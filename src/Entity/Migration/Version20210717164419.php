<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210717164419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make some fields that should never be nullable non-nullable.';
    }

    public function preUp(Schema $schema): void
    {
        $this->connection->update(
            'api_keys',
            ['comment' => ''],
            ['comment' => null]
        );

        $this->connection->update(
            'custom_field',
            ['short_name' => ''],
            ['short_name' => null]
        );

        $this->connection->update(
            'station',
            ['name' => ''],
            ['name' => null]
        );

        $this->connection->update(
            'station',
            ['short_name' => ''],
            ['short_name' => null]
        );

        $this->connection->update(
            'users',
            ['email' => ''],
            ['email' => null]
        );

        $this->connection->update(
            'users',
            ['auth_password' => ''],
            ['auth_password' => null]
        );
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_keys CHANGE comment comment VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE custom_field CHANGE short_name short_name VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE station CHANGE name name VARCHAR(100) NOT NULL, CHANGE short_name short_name VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE users CHANGE email email VARCHAR(100) NOT NULL, CHANGE auth_password auth_password VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_keys CHANGE comment comment VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`');
        $this->addSql('ALTER TABLE custom_field CHANGE short_name short_name VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`');
        $this->addSql('ALTER TABLE station CHANGE name name VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, CHANGE short_name short_name VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`');
        $this->addSql('ALTER TABLE users CHANGE email email VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, CHANGE auth_password auth_password VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`');
    }
}
