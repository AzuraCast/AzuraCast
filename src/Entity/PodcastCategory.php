<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[
    ORM\Entity,
    ORM\Table(name: 'podcast_category'),
    Attributes\Auditable
]
class PodcastCategory implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    public const CATEGORY_SEPARATOR = '|';

    #[ORM\ManyToOne(inversedBy: 'categories')]
    #[ORM\JoinColumn(name: 'podcast_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Podcast $podcast;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    protected string $category;

    public function __construct(Podcast $podcast, string $category)
    {
        $this->podcast = $podcast;
        $this->category = $this->truncateString($category);
    }

    public function getPodcast(): Podcast
    {
        return $this->podcast;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getTitle(): string
    {
        return (explode(self::CATEGORY_SEPARATOR, $this->category))[0];
    }

    public function getSubTitle(): ?string
    {
        return (str_contains($this->category, self::CATEGORY_SEPARATOR))
            ? (explode(self::CATEGORY_SEPARATOR, $this->category))[1]
            : null;
    }

    /**
     * @return mixed[]
     */
    public static function getAvailableCategories(): array
    {
        $categories = [
            'Arts' => [
                'Books',
                'Design',
                'Fashion & Beauty',
                'Food',
                'Performing Arts',
                'Visual Arts',
            ],
            'Business' => [
                'Careers',
                'Entrepreneurship',
                'Investing',
                'Management',
                'Marketing',
                'Non-Profit',
            ],
            'Comedy' => [
                'Comedy Interviews',
                'Improv',
                'Stand-Up',
            ],
            'Education' => [
                'Courses',
                'How To',
                'Language Learning',
                'Self-Improvement',
            ],
            'Fiction' => [
                'Comedy Fiction',
                'Drama',
                'Science Fiction',
            ],
            'Government' => [
                '',
            ],
            'History' => [
                '',
            ],
            'Health & Fitness' => [
                'Alternative Health',
                'Fitness',
                'Medicine',
                'Mental Health',
                'Nutrition',
                'Sexuality',
            ],
            'Kids & Family' => [
                'Parenting',
                'Pets & Animals',
                'Stories for Kids',
            ],
            'Leisure' => [
                'Animation & Manga',
                'Automotive',
                'Aviation',
                'Crafts',
                'Games',
                'Hobbies',
                'Home & Garden',
                'Video Games',
            ],
            'Music' => [
                'Music Commentary',
                'Music History',
                'Music Interviews',
            ],
            'News' => [
                'Business News',
                'Daily News',
                'Entertainment News',
                'News Commentary',
                'Politics',
                'Sports News',
                'Tech News',
            ],
            'Religion & Spirituality' => [
                'Buddhism',
                'Christianity',
                'Hinduism',
                'Islam',
                'Judaism',
                'Religion',
                'Spirituality',
            ],
            'Science' => [
                'Astronomy',
                'Chemistry',
                'Earth Sciences',
                'Life Sciences',
                'Mathematics',
                'Natural Sciences',
                'Nature',
                'Physics',
                'Social Sciences',
            ],
            'Society & Culture' => [
                'Documentary',
                'Personal Journals',
                'Philosophy',
                'Places & Travel',
                'Relationships',
            ],
            'Sports' => [
                'Baseball',
                'Basketball',
                'Cricket',
                'Fantasy Sports',
                'Football',
                'Golf',
                'Hockey',
                'Rugby',
                'Running',
                'Soccer',
                'Swimming',
                'Tennis',
                'Volleyball',
                'Wilderness',
                'Wrestling',
            ],
            'Technology' => [
                '',
            ],
            'True Crime' => [
                '',
            ],
            'TV & Film' => [
                'After Shows',
                'Film History',
                'Film Interviews',
                'Film Reviews',
                'TV Reviews',
            ],
        ];

        $categorySelect = [];
        foreach ($categories as $categoryName => $subTitles) {
            foreach ($subTitles as $subTitle) {
                if ('' === $subTitle) {
                    $categorySelect[$categoryName] = $categoryName;
                } else {
                    $selectKey = $categoryName . self::CATEGORY_SEPARATOR . $subTitle;
                    $categorySelect[$selectKey] = $categoryName . ' > ' . $subTitle;
                }
            }
        }

        return $categorySelect;
    }
}
