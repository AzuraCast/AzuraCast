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

    public static function load()
    {
        $em = self::getEntityManager();

        // Pull featured images.
        $timestamp_threshold = strtotime('-6 weeks');

        $api_params = array(
            'api_key'       => 'Hp1W4lpJ0dhHA7pOGih0yow02ZXAFHdiIR5bzFS67C0xlERPAZ',
            'limit'         => 10,
        );
        $api_url = 'http://api.tumblr.com/v2/blog/news.ponyvillelive.com/posts/photo?'.http_build_query($api_params);

        $results_raw = @file_get_contents($api_url);
        $news_items = array();

        if ($results_raw)
        {
            $results = json_decode($results_raw, true);
            $posts = $results['response']['posts'];

            $network_news = array();
            foreach((array)$posts as $post)
            {
                $image = $post['photos'][0]['original_size'];

                if ($image['height'] > 250)
                    continue;

                // Copy the image to the local static directory (for SSL and other caching support).
                $image_url = $image['url'];
                $image_url_basename = basename($image_url);
                
                $local_path_base = 'rotators/'.$image_url_basename;

                $local_path = DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $local_path_base);
                $local_url = $local_path_base;

                if (!file_exists($local_path))
                    @copy($image_url, $local_path);

                $tags = array_map('strtolower', (array)$post['tags']);
                if (in_array('archive', $tags))
                    continue;

                $description = \DF\Utilities::truncateText(strip_tags($post['caption']), 250);

                if (strpos($description, ':') !== FALSE)
                {
                    list($title, $description) = explode(':', $description, 2);
                }
                else
                {
                    $title = $description;
                    $description = NULL;
                }

                $news_row = array(
                    'id'        => 'tumblr_'.$post['id'],
                    'title'     => trim($title),
                    'body'      => trim($description),
                    'image_url' => $local_url,
                    'web_url'   => $post['post_url'],
                    'timestamp' => $post['timestamp'],
                );

                if ($news_row['timestamp'] >= $timestamp_threshold)
                    $news_items[] = $news_row;
            }

            // Delete current rotator contents.
            $em->createQuery('DELETE FROM '.__CLASS__.' nn')->execute();

            foreach($news_items as $item)
            {
                $record = new self;
                $record->fromArray($item);

                $em->persist($record);
            }

            $em->flush();
        }
    }
}