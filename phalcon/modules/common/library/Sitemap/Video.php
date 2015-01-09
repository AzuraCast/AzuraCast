<?php

namespace Baseapp\Library\Sitemap;

class Video implements \Baseapp\Library\Sitemap\SitemapInterface
{

    private $_attributes = array(
        'thumbnail_loc' => null,
        'title' => null,
        'description' => null,
        'content_loc' => null,
        'expiration_date' => null,
        'rating' => null,
        'view_count' => null,
        'publication_date' => null,
        'category' => null,
        'family_friendly' => null,
        'requires_subscription' => null
    );
    protected $_player_loc = array();

    /**
     * @param string $thumbnail_loc A URL pointing to the URL for the video
     * thumbnail image file. We can accept most image sizes/types but recommend
     * your thumbs are at least 160x120 in .jpg, .png, or. gif formats.
     */
    public function set_thumbnail_loc($thumbnail_loc)
    {
        $this->_attributes['thumbnail_loc'] = $thumbnail_loc;
    }

    /**
     * @param string $title The title of the video. Limited to 100 characters.
     */
    public function set_title($title)
    {
        if (strlen($title) > 100) {
            throw new \InvalidArgumentException('Your title can be no longer than 100 characters');
        }

        $this->_attributes['title'] = $title;
    }

    /**
     * @param string $description The description of the video. Descriptions
     * longer than 2048 characters will be truncated.
     */
    public function set_description($description)
    {
        if (strlen($description) > 2048) {
            throw new \InvalidArgumentException('Your description can be no longer than 2048 characters');
        }

        $this->_attributes['description'] = $description;
    }

    /**
     * @param string $loc
     * @see http://www.google.com/support/webmasters/bin/answer.py?answer=80472
     */
    public function set_content_loc($loc)
    {
        if (!filter_var($loc, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException($loc . ' is not a valid URL');
        }

        $this->_attributes['content_loc'] = $loc;
    }

    /**
     * @param string $loc
     * @param boolean $allow_embed
     * @param string $autostart
     * @see http://www.google.com/support/webmasters/bin/answer.py?answer=80472
     */
    public function set_player_loc($loc, $allow_embed, $autostart)
    {
        if (!filter_var($loc, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException($loc . ' is not a valid URL');
        }

        $allow_embed = $allow_embed ? 'yes' : 'no';

        $this->_player_loc['loc'] = $loc;
        $this->_player_loc['allow_embed'] = $allow_embed;
        $this->_player_loc['autostart'] = $autostart;
    }

    /**
     * @param integer $duration The duration of the video in seconds. Value must
     * be between 0 and 28800 (8 hours). Non-digit characters are disallowed.
     */
    public function set_duration($duration)
    {
        $eight_hours = 28800;

        $duration = (int) $duration;

        if ($duration > $eight_hours OR $duration < 0) {
            throw new \InvalidArgumentException('Duration must be between 0 and 8 hours');
        }

        $this->_attributes['duration'] = $duration;
    }

    /**
     * @param integer $expiration_date The date after which the video will no
     * longer be available, in unixtimestamp format.
     */
    public function set_expiration_date($expiration_date)
    {
        $this->_attributes['expiration_date'] = \Baseapp\Library\Sitemap::date_format($expiration_date);
    }

    /**
     * @param float $rating The rating of the video. The value must be float
     * number in the range 0.0-5.0.
     */
    public function set_rating($rating)
    {
        if ($rating < 0 OR $rating > 5) {
            throw new \OutOfRangeException('Rating must be in the range of 0.0 to 5.0');
        }

        $this->_attributes['rating'] = $rating;
    }

    /**
     *
     * @param type $loc
     * @todo finish.
     */
    public function set_content_segment_loc($loc)
    {
        
    }

    /**
     * @param string $view_count The number of times the video has been viewed
     */
    public function set_view_count($view_count)
    {
        $this->_attributes['view_count'] = (int) $view_count;
    }

    /**
     * @param integer $publication_date The date the video was first published,
     * in unixtimestamp format.
     */
    public function set_publication_date($publication_date)
    {
        $this->_attributes['publication_date'] = \Baseapp\Library\Sitemap::date_format($publication_date);
    }

    /**
     * @param array $tag Array of tags, a maximum of 32 tags is permitted
     */
    public function set_tag($tag)
    {
        if (is_array($tag)) {
            foreach ($tag as $row) {
                $this->set_tag($row);
            }
        }

        if (count($this->_attributes['tags']) > 32) {
            throw new \OverflowException('A maximum of 32 tags are permitted');
        }

        $this->_attributes['tags'][] = array('tag' => $tag);
    }

    /**
     * @param string $category The video's category. For example, cooking. The
     * value should be a string no longer than 256 characters. In general,
     * categories are broad groupings of content by subject. Usually a video will
     * belong to a single category. For example, a site about cooking could have
     * categories for Broiling, Baking, and Grilling
     */
    public function set_category($category)
    {
        if (strlen($category) > 256) {
            throw new \InvalidArgumentException('The category should be no longer than 256 characters long.');
        }

        $this->_attributes['category'] = $category;
    }

    /**
     * @param string $family_friendly "No" if the video should be available only
     * to users with SafeSearch turned off.
     */
    public function set_family_friendly($family_friendly)
    {
        $family_friendly = $family_friendly ? 'yes' : 'no';

        $this->_attributes['family_friendly'] = $family_friendly;
    }

    /**
     * Accepts an array in the form of
     *
     * $array('country' => true|false);
     *
     * @param type $countries
     * @todo finish
     */
    public function set_restriction($countries)
    {
        
    }

    /**
     *
     * @param type $loc
     * @todo finish
     */
    public function set_gallery_loc($loc)
    {
        $this->_attributes['gallery_loc'] = $loc;
    }

    /**
     * @param float $price The price to download or view the video. The required
     * attribute currency specifies the currency in ISO 4217 format. More than one
     * <video:price> element can be listed (for example, in order to specify various
     * currencies).
     * @todo finish
     */
    public function set_price($price)
    {
        
    }

    /**
     * @param bool $subscription Indicates whether a subscription (either paid or free)
     * is required to view the video. Allowed values are yes or no.
     */
    public function set_requires_subscription($requires_subscription)
    {
        $requires_subscription = $requires_subscription ? 'yes' : 'no';

        $this->_attributes['requires_subscription'] = $requires_subscription;
    }

    /**
     * @param string $uploader A name or handle of the video’s uploader. Only one
     * <video:uploader> is allowed per video. The optional attribute info specifies
     * the URL of a webpage with additional information about this uploader. This
     * URL must be on the same domain as the <loc> tag.
     * @todo add info attribute.
     */
    public function set_uploader($uploader)
    {
        $this->_attributes['uploader'] = $uploader;
    }

    public function create()
    {
        // Here we need to create a new DOMDocument. This is so we can re-import the
        // DOMElement at the other end.
        $document = new \DOMDocument;

        // Video element
        $video = $document->createElement('video:video');

        $document->appendChild($video);

        /**
         * Small recursive function to add attributes to the document.
         */
        $append_attributes = function($attributes) use ($video, $document) {
            foreach ($attributes as $name => $value) {
                if (null !== $value) {
                    if (is_array($value)) {
                        $append_attributes($value);
                    }

                    $element = $document->createElement('video:' . $name);
                    $element->appendChild($document->createTextNode($value));
                    $video->appendChild($element);
                }
            }
        };

        $append_attributes($this->_attributes);

        // @todo append: uploader, restriction and player_loc.

        return $video;
    }

    public function root(\DOMElement & $root)
    {
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:video', 'http://www.google.com/schemas/sitemap-video/1.1');
    }

}
