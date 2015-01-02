<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="network_news")
 * @Entity
 */
class NetworkNews extends \DF\Doctrine\Entity
{
    /**
     * @Column(name="guid", type="string", length=128, nullable=true)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    protected $id;

    /** @Column(name="title", type="string", length=400, nullable=true) */
    protected $title;

    /** @Column(name="body", type="text", nullable=true) */
    protected $body;

    /** @Column(name="image_url", type="string", length=100, nullable=true) */
    protected $image_url;

    /** @Column(name="web_url", type="string", length=100, nullable=true) */
    protected $web_url;

    /** @Column(name="timestamp", type="integer", nullable=true) */
    protected $timestamp;

    /**
     * Static Functions
     */

    public static function fetch($articles_num = 5)
    {
        $em = self::getEntityManager();
        $results_raw = $em->createQuery('SELECT nn FROM '.__CLASS__.' nn ORDER BY nn.timestamp DESC')
            ->getArrayResult();

        $network_news = array();
        foreach($results_raw as $row)
        {
            $network_news[] = array(
                'image'     => \DF\Url::content($row['image_url']),
                'url'       => $row['web_url'],
                'title'     => $row['title'],
                'description' => $row['body'],
                'timestamp' => $row['timestamp'],
            );
        }

        if ($articles_num)
            $network_news = array_slice($network_news, 0, $articles_num);

        return $network_news;
    }
}