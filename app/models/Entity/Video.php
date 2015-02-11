<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="video")
 * @Entity
 */
class Video extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->is_active = 1;
        $this->weight = 0;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="is_active", type="boolean") */
    protected $is_active;

    /** @Column(name="name", type="string", length=100, nullable=true) */
    protected $name;

    public function getShortName()
    {
        return self::getStationShortName($this->name);
    }

    /** @Column(name="weight", type="smallint") */
    protected $weight;

    /** @Column(name="genre", type="string", length=50, nullable=true) */
    protected $genre;

    /** @Column(name="country", type="string", length=5, nullable=true) */
    protected $country;

    public function getCountryName()
    {
        if ($this->country)
            return \PVL\Internationalization::getLanguageName($this->country);
        else
            return '';
    }

    /** @Column(name="image_url", type="string", length=100, nullable=true) */
    protected $image_url;

    public function setImageUrl($new_url)
    {
        if ($new_url)
        {
            if ($this->image_url && $this->image_url != $new_url)
                @unlink(DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.$this->image_url);

            $new_path = DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.$new_url;
            \DF\Image::resizeImage($new_path, $new_path, 150, 150);

            $this->image_url = $new_url;
        }
    }

    /** @Column(name="contact_email", type="string", length=255, nullable=true) */
    protected $contact_email;

    /** @Column(name="web_url", type="string", length=100, nullable=true) */
    protected $web_url;

    /** @Column(name="irc", type="string", length=25, nullable=true) */
    protected $irc;

    /** @Column(name="twitter_url", type="string", length=100, nullable=true) */
    protected $twitter_url;

    public function setTwitterUrl($url)
    {
        if (substr($url, 0, 4) == "http" || empty($url))
        {
            $this->twitter_url = $url;
        }
        else
        {
            $url = 'http://www.twitter.com/'.str_replace('@', '', $url);
            $this->twitter_url = $url;
        }
    }

    /** @Column(name="gcal_url", type="string", length=150, nullable=true) */
    protected $gcal_url;

    /** @Column(name="facebook_url", type="string", length=150, nullable=true) */
    protected $facebook_url;

    /** @Column(name="tumblr_url", type="string", length=150, nullable=true) */
    protected $tumblr_url;

    /** @Column(name="stream_url", type="string", length=150, nullable=true) */
    protected $stream_url;

    /** @Column(name="nowplaying_url", type="string", length=100, nullable=true) */
    protected $nowplaying_url;

    /** @Column(name="nowplaying_data", type="json", nullable=true) */
    protected $nowplaying_data;

    /** @Column(name="deleted_at", type="datetime", nullable=true) */
    protected $deleted_at;

    /**
     * Static Functions
     */

    public static function getStationShortName($name)
    {
        return strtolower(preg_replace("/[^A-Za-z0-9_]/", '', str_replace(' ', '_', $name)));
    }

    public static function getStationClassName($name)
    {
        $name = preg_replace("/[^A-Za-z0-9_ ]/", '', $name);
        $name = str_replace('_', ' ', $name);
        $name = str_replace(' ', '', $name);
        return $name;
    }

    // Retrieve the API version of the object/array.
    public static function api($row)
    {
        if ($row instanceof self)
            $row = $row->toArray();

        $api = array(
            'id'        => (int)$row['id'],
            'name'      => $row['name'],
            'shortcode' => self::getStationShortName($row['name']),
            'genre'     => $row['genre'],
            'category'  => $row['category'],
            'image_url' => \DF\Url::content($row['image_url']),
            'web_url'   => $row['web_url'],
            'twitter_url' => $row['twitter_url'],
        );

        return $api;
    }
}