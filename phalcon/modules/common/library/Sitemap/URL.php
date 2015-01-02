<?php

namespace Baseapp\Library\Sitemap;

class URL implements \Baseapp\Library\Sitemap\SitemapInterface
{

    private $attributes = array(
        'loc' => null,
        'lastmod' => null,
        'changefreq' => null,
        'priority' => null,
    );

    /**
     * URL of the page. This URL must begin with the protocol (such as http) and end
     * with a trailing slash, if your web server requires it. This value must be
     * less than 2,048 characters.
     * @see http://www.sitemaps.org/protocol.php
     * @param string $location
     */
    public function set_loc($location)
    {
        if (strlen($location) > 2048) {
            throw new \LengthException('The location was too long, maximum length of 2,048 characters.');
        }

        $location = \Baseapp\Library\Sitemap::encode($location);

        if (!filter_var($location, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('The location was not a valid URL');
        }

        $this->attributes['loc'] = $location;

        return $this;
    }

    /**
     * The date of last modification of the file.
     * @param integer $lastmod Unix timestamp
     */
    public function set_last_mod($lastmod)
    {
        $this->attributes['lastmod'] = \Baseapp\Library\Sitemap::date_format($lastmod);

        return $this;
    }

    /**
     * How frequently the page is likely to change
     * @param string $change_frequency
     */
    public function set_change_frequency($change_frequency)
    {
        $change_frequency = (string) $change_frequency;

        $frequencies = array('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never');

        if (!in_array($change_frequency, $frequencies)) {
            throw new \InvalidArgumentException('Invalid change frequency');
        }

        $this->attributes['changefreq'] = $change_frequency;

        return $this;
    }

    /**
     * The priority of this URL relative to other URLs on your site. Ranges from
     * 0 to 1, the default is 0.5
     * @param integer $priority
     */
    public function set_priority($priority)
    {
        if (!is_numeric($priority)) {
            throw new \InvalidArgumentException('The priority was not a numeric value.');
        }

        if ($priority > 1 OR $priority < 0) {
            throw new \RangeException('Priority must be between 0 and 1 (inclusive).');
        }

        /*
         * @TODO: Deal with locales that don't use a period as their decimal point.
         */

        $this->attributes['priority'] = $priority;

        return $this;
    }

    /**
     * @var Sitemap Interface
     */
    private $driver = null;

    /**
     *
     * @param <type> $driver
     */
    public function __construct(\Baseapp\Library\Sitemap\SitemapInterface $driver = null)
    {
        $this->driver = $driver;
    }

    /**
     * Creates the URL node and decorates it with additional sitemap information.
     */
    public function create()
    {
        $document = new \DOMDocument;

        $url_node = $document->createElement('url');

        foreach ($this->attributes as $name => $value) {
            // The loc attribute is required.
            if (null === $this->attributes['loc']) {
                throw new \RuntimeException('loc is required');
            }

            // Add attributes that aren't empty.
            if (null !== $value) {
                $url_node->appendChild(new \DOMElement($name, $value));
            }
        }

        // If a specialised sitemap was used, import it's data here.
        if (null !== $this->driver) {
            $url_node->appendChild($document->importNode($this->driver->create(), true));
        }

        return $url_node;
    }

    public function root(\DOMElement & $root)
    {
        // Add urlset namespace.
        $root->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');

        if (null !== $this->driver) {
            $this->driver->root($root);
        }
    }

}
