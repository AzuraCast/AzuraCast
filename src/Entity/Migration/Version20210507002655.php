<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210507002655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE podcast_category (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, sub_title VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE station_podcast (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, title VARCHAR(255) NOT NULL, link VARCHAR(255) DEFAULT NULL, description LONGTEXT NOT NULL, language VARCHAR(2) NOT NULL, unique_id VARCHAR(25) DEFAULT NULL, INDEX IDX_9E4EA0E321BDB235 (station_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE station_podcast_category (id INT AUTO_INCREMENT NOT NULL, podcast_id INT DEFAULT NULL, category_id INT DEFAULT NULL, INDEX IDX_B2227F14786136AB (podcast_id), INDEX IDX_B2227F1412469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE station_podcast_episode (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, podcast_id INT NOT NULL, title VARCHAR(255) NOT NULL, link VARCHAR(255) DEFAULT NULL, description LONGTEXT NOT NULL, publish_at INT DEFAULT NULL, explicit TINYINT(1) NOT NULL, created_at INT NOT NULL, unique_id VARCHAR(25) DEFAULT NULL, INDEX IDX_B872FE2621BDB235 (station_id), INDEX IDX_B872FE26786136AB (podcast_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE station_podcast_media (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, episode_id INT DEFAULT NULL, original_name VARCHAR(200) NOT NULL, length NUMERIC(7, 2) NOT NULL, length_text VARCHAR(10) NOT NULL, path VARCHAR(500) NOT NULL, mime_type VARCHAR(255) NOT NULL, modified_time INT NOT NULL, unique_id VARCHAR(25) DEFAULT NULL, INDEX IDX_8CA501DE21BDB235 (station_id), UNIQUE INDEX UNIQ_8CA501DE362B62A0 (episode_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE station_podcast ADD CONSTRAINT FK_9E4EA0E321BDB235 FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_podcast_category ADD CONSTRAINT FK_B2227F14786136AB FOREIGN KEY (podcast_id) REFERENCES station_podcast (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_podcast_category ADD CONSTRAINT FK_B2227F1412469DE2 FOREIGN KEY (category_id) REFERENCES podcast_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_podcast_episode ADD CONSTRAINT FK_B872FE2621BDB235 FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_podcast_episode ADD CONSTRAINT FK_B872FE26786136AB FOREIGN KEY (podcast_id) REFERENCES station_podcast (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_podcast_media ADD CONSTRAINT FK_8CA501DE21BDB235 FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_podcast_media ADD CONSTRAINT FK_8CA501DE362B62A0 FOREIGN KEY (episode_id) REFERENCES station_podcast_episode (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE station ADD podcasts_storage_location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B123303CD0 FOREIGN KEY (podcasts_storage_location_id) REFERENCES storage_location (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_9F39F8B123303CD0 ON station (podcasts_storage_location_id)');
    }

    public function postUp(Schema $schema): void
    {
        $categories = [
            [
                'title' => 'Arts',
                'sub_title' => 'Books',
            ],
            [
                'title' => 'Arts',
                'sub_title' => 'Design',
            ],
            [
                'title' => 'Arts',
                'sub_title' => 'Fashion & Beauty',
            ],
            [
                'title' => 'Arts',
                'sub_title' => 'Food',
            ],
            [
                'title' => 'Arts',
                'sub_title' => 'Performing Arts',
            ],
            [
                'title' => 'Arts',
                'sub_title' => 'Visual Arts',
            ],
            [
                'title' => 'Business',
                'sub_title' => 'Careers',
            ],
            [
                'title' => 'Business',
                'sub_title' => 'Entrepreneurship',
            ],
            [
                'title' => 'Business',
                'sub_title' => 'Investing',
            ],
            [
                'title' => 'Business',
                'sub_title' => 'Management',
            ],
            [
                'title' => 'Business',
                'sub_title' => 'Marketing',
            ],
            [
                'title' => 'Business',
                'sub_title' => 'Non-Profit',
            ],
            [
                'title' => 'Comedy',
                'sub_title' => 'Comedy Interviews',
            ],
            [
                'title' => 'Comedy',
                'sub_title' => 'Improv',
            ],
            [
                'title' => 'Comedy',
                'sub_title' => 'Stand-Up',
            ],
            [
                'title' => 'Education',
                'sub_title' => 'Courses',
            ],
            [
                'title' => 'Education',
                'sub_title' => 'How To',
            ],
            [
                'title' => 'Education',
                'sub_title' => 'Language Learning',
            ],
            [
                'title' => 'Education',
                'sub_title' => 'Self-Improvement',
            ],
            [
                'title' => 'Fiction',
                'sub_title' => 'Comedy Fiction',
            ],
            [
                'title' => 'Fiction',
                'sub_title' => 'Drama',
            ],
            [
                'title' => 'Fiction',
                'sub_title' => 'Science Fiction',
            ],
            [
                'title' => 'Government',
                'sub_title' => null,
            ],
            [
                'title' => 'History',
                'sub_title' => null,
            ],
            [
                'title' => 'Health & Fitness',
                'sub_title' => 'Alternative Health',
            ],
            [
                'title' => 'Health & Fitness',
                'sub_title' => 'Fitness',
            ],
            [
                'title' => 'Health & Fitness',
                'sub_title' => 'Medicine',
            ],
            [
                'title' => 'Health & Fitness',
                'sub_title' => 'Mental Health',
            ],
            [
                'title' => 'Health & Fitness',
                'sub_title' => 'Nutrition',
            ],
            [
                'title' => 'Health & Fitness',
                'sub_title' => 'Sexuality',
            ],
            [
                'title' => 'Kids & Family',
                'sub_title' => 'Education for Kids',
            ],
            [
                'title' => 'Kids & Family',
                'sub_title' => 'Parenting',
            ],
            [
                'title' => 'Kids & Family',
                'sub_title' => 'Pets & Animals',
            ],
            [
                'title' => 'Kids & Family',
                'sub_title' => 'Stories for Kids',
            ],
            [
                'title' => 'Leisure',
                'sub_title' => 'Animation & Manga',
            ],
            [
                'title' => 'Leisure',
                'sub_title' => 'Automotive',
            ],
            [
                'title' => 'Leisure',
                'sub_title' => 'Aviation',
            ],
            [
                'title' => 'Leisure',
                'sub_title' => 'Crafts',
            ],
            [
                'title' => 'Leisure',
                'sub_title' => 'Games',
            ],
            [
                'title' => 'Leisure',
                'sub_title' => 'Hobbies',
            ],
            [
                'title' => 'Leisure',
                'sub_title' => 'Home & Garden',
            ],
            [
                'title' => 'Leisure',
                'sub_title' => 'Video Games',
            ],
            [
                'title' => 'Music',
                'sub_title' => 'Music Commentary',
            ],
            [
                'title' => 'Music',
                'sub_title' => 'Music History',
            ],
            [
                'title' => 'Music',
                'sub_title' => 'Music Interviews',
            ],
            [
                'title' => 'News',
                'sub_title' => 'Business News',
            ],
            [
                'title' => 'News',
                'sub_title' => 'Daily News',
            ],
            [
                'title' => 'News',
                'sub_title' => 'Entertainment News',
            ],
            [
                'title' => 'News',
                'sub_title' => 'News Commentary',
            ],
            [
                'title' => 'News',
                'sub_title' => 'Politics',
            ],
            [
                'title' => 'News',
                'sub_title' => 'Sports News',
            ],
            [
                'title' => 'News',
                'sub_title' => 'Tech News',
            ],
            [
                'title' => 'Religion & Spirituality',
                'sub_title' => 'Buddhism',
            ],
            [
                'title' => 'Religion & Spirituality',
                'sub_title' => 'Christianity',
            ],
            [
                'title' => 'Religion & Spirituality',
                'sub_title' => 'Hinduism',
            ],
            [
                'title' => 'Religion & Spirituality',
                'sub_title' => 'Islam',
            ],
            [
                'title' => 'Religion & Spirituality',
                'sub_title' => 'Judaism',
            ],
            [
                'title' => 'Religion & Spirituality',
                'sub_title' => 'Religion',
            ],
            [
                'title' => 'Religion & Spirituality',
                'sub_title' => 'Spirituality',
            ],
            [
                'title' => 'Science',
                'sub_title' => 'Astronomy',
            ],
            [
                'title' => 'Science',
                'sub_title' => 'Chemistry',
            ],
            [
                'title' => 'Science',
                'sub_title' => 'Earth Sciences',
            ],
            [
                'title' => 'Science',
                'sub_title' => 'Life Sciences',
            ],
            [
                'title' => 'Science',
                'sub_title' => 'Mathematics',
            ],
            [
                'title' => 'Science',
                'sub_title' => 'Natural Sciences',
            ],
            [
                'title' => 'Science',
                'sub_title' => 'Nature',
            ],
            [
                'title' => 'Science',
                'sub_title' => 'Physics',
            ],
            [
                'title' => 'Science',
                'sub_title' => 'Social Sciences',
            ],
            [
                'title' => 'Society & Culture',
                'sub_title' => 'Documentary',
            ],
            [
                'title' => 'Society & Culture',
                'sub_title' => 'Personal Journals',
            ],
            [
                'title' => 'Society & Culture',
                'sub_title' => 'Philosophy',
            ],
            [
                'title' => 'Society & Culture',
                'sub_title' => 'Places & Travel',
            ],
            [
                'title' => 'Society & Culture',
                'sub_title' => 'Relationships',
            ],
            [
                'title' => 'Sports',
                'sub_title' => 'Baseball',
            ],
            [
                'title' => 'Sports',
                'sub_title' => 'Basketball',
            ],
            [
                'title' => 'Sports',
                'sub_title' => 'Cricket',
            ],
            [
                'title' => 'Sports',
                'sub_title' => 'Fantasy Sports',
            ],
            [
                'title' => 'Sports',
                'sub_title' => 'Football',
            ],
            [
                'title' => 'Sports',
                'sub_title' => 'Golf',
            ],
            [
                'title' => 'Sports',
                'sub_title' => 'Hockey',
            ],
            [
                'title' => 'Sports',
                'sub_title' => 'Rugby',
            ],
            [
                'title' => 'Sports',
                'sub_title' => 'Running',
            ],
            [
                'title' => 'Sports',
                'sub_title' => 'Soccer',
            ],
            [
                'title' => 'Sports',
                'sub_title' => 'Swimming',
            ],
            [
                'title' => 'Sports',
                'sub_title' => 'Tennis',
            ],
            [
                'title' => 'Sports',
                'sub_title' => 'Volleyball',
            ],
            [
                'title' => 'Sports',
                'sub_title' => 'Wilderness',
            ],
            [
                'title' => 'Sports',
                'sub_title' => 'Wrestling',
            ],
            [
                'title' => 'Technology',
                'sub_title' => null,
            ],
            [
                'title' => 'True Crime',
                'sub_title' => null,
            ],
            [
                'title' => 'TV & Film',
                'sub_title' => 'After Shows',
            ],
            [
                'title' => 'TV & Film',
                'sub_title' => 'Film History',
            ],
            [
                'title' => 'TV & Film',
                'sub_title' => 'Film Interviews',
            ],
            [
                'title' => 'TV & Film',
                'sub_title' => 'Film Reviews',
            ],
            [
                'title' => 'TV & Film',
                'sub_title' => 'TV Reviews',
            ],
        ];

        foreach ($categories as $category) {
            $this->connection->insert(
                'podcast_category',
                $category
            );
        }

        $stations = $this->connection->fetchAllAssociative(
            'SELECT id, radio_base_dir FROM station WHERE podcasts_storage_location_id IS NULL ORDER BY id ASC'
        );

        foreach ($stations as $row) {
            $stationId = $row['id'];

            $baseDir = $row['radio_base_dir'];

            $this->connection->insert(
                'storage_location',
                [
                    'type' => 'station_podcasts',
                    'adapter' => 'local',
                    'path' => $baseDir . '/podcasts',
                    'storage_quota' => null,
                ]
            );

            $podcastsStorageLocationId = $this->connection->lastInsertId('storage_location');

            $this->connection->update(
                'station',
                [
                    'podcasts_storage_location_id' => $podcastsStorageLocationId,
                ],
                [
                    'id' => $stationId,
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE station_podcast_category DROP FOREIGN KEY FK_B2227F1412469DE2');
        $this->addSql('ALTER TABLE station_podcast_category DROP FOREIGN KEY FK_B2227F14786136AB');
        $this->addSql('ALTER TABLE station_podcast_episode DROP FOREIGN KEY FK_B872FE26786136AB');
        $this->addSql('ALTER TABLE station_podcast_media DROP FOREIGN KEY FK_8CA501DE362B62A0');
        $this->addSql('DROP TABLE podcast_category');
        $this->addSql('DROP TABLE station_podcast');
        $this->addSql('DROP TABLE station_podcast_category');
        $this->addSql('DROP TABLE station_podcast_episode');
        $this->addSql('DROP TABLE station_podcast_media');
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B123303CD0');
        $this->addSql('DROP INDEX IDX_9F39F8B123303CD0 ON station');
        $this->addSql('ALTER TABLE station DROP podcasts_storage_location_id');
    }
}
