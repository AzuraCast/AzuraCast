<?php

namespace Baseapp\Library\Sitemap;

class News implements \Baseapp\Library\Sitemap\SitemapInterface
{

    /**
     * @var array publication details
     */
    protected $_publication = array(
        'publication' => null,
        'lang' => null
    );

    /**
     * @var array article attributes
     */
    protected $_attributes = array(
        'access' => null,
        'genres' => null,
        'publication_date' => null,
        'title' => null,
        'keywords' => null,
        'stock_tickers' => null,
    );

    /**
     * @param string $publication name of the news publication. It must exactly
     * match the name as it appears on your articles in news.google.com, omitting
     * any trailing parentheticals.
     */
    public function set_publication($publication)
    {
        $this->_publication['publication'] = $publication;

        return $this;
    }

    /**
     * @param string $lang hould be an ISO 639 Language Code (either 2 or 3 letters).
     * Exception: For Chinese, please use zh-cn for Simplified Chinese or zh-tw for
     * Traditional Chinese.
     *
     * @see http://www.loc.gov/standards/iso639-2/php/code_list.php
     */
    public function set_lang($lang)
    {
        if ('zh-cn' !== $lang AND 'zh-tw' !== $lang) {
            if (!preg_match('/^[a-z]{2,3}$/', $lang)) {
                throw new \InvalidArgumentException('Invalid language code');
            }
        }

        $this->_publication['lang'] = $lang;

        return $this;
    }

    /**
     * @param string $access Possible values include "Subscription" or "Registration",
     * describing the accessibility of the article. If the article is accessible to
     * Google News readers without a registration or subscription, this tag should
     * be omitted.
     */
    public function set_access($access)
    {
        if ('subscription' !== strtolower($access) AND 'registration' !== strtolower($access)) {
            throw new \InvalidArgumentException('Invalid access string');
        }

        $this->_attributes['access'] = $access;

        return $this;
    }

    /**
     * @param string $genres A comma-separated list of properties characterizing the
     * content of the article, such as "PressRelease" or "UserGenerated." See Google
     * News content properties for a list of possible values. Your content must be
     * labeled accurately, in order to provide a consistent experience for our users.
     *
     * @see http://www.google.com/support/webmasters/bin/answer.py?answer=93992
     */
    public function set_genres(array $genres)
    {
        $allowed = array('PressRelease', 'Satire', 'Blog', 'OpEd', 'Opinion', 'UserGenerated');

        $difference = array_diff($genres, $allowed);

        if (count($difference) > 0) {
            throw new \InvalidArgumentException('Invalid genre passed');
        }

        $this->_attributes['genres'] = implode(',', $genres);

        return $this;
    }

    /**
     * @param integer $date Article publication date in unixtimestamp format
     */
    public function set_publication_date($date)
    {
        $this->_attributes['publication_date'] = \Baseapp\Library\Sitemap::date_format($date);

        return $this;
    }

    /**
     * @param string $title The title of the news article. Note: The title may be
     * truncated for space reasons when shown on Google News.
     */
    public function set_title($title)
    {
        $this->_attributes['title'] = $title;

        return $this;
    }

    /**
     * @param string $keywords A comma-separated list of keywords describing the
     * topic of the article. Keywords may be drawn from, but are not limited to,
     * the list of existing Google News keywords.
     *
     * @see http://www.google.com/support/webmasters/bin/answer.py?answer=116037
     */
    public function set_keywords(array $keywords)
    {
        $this->_attributes['keywords'] = implode(',', $keywords);

        return $this;
    }

    /**
     * @param string $tickers A comma-separated list of up to 5 stock tickers of
     * the companies, mutual funds, or other financial entities that are the main
     * subject of the article.
     *
     * @see http://finance.google.com/
     */
    public function set_stock_tickers(array $tickers)
    {
        if (count($tickers) > 5) {
            throw new \OutOfRangeException('You can\'t provide more than 5 tickers');
        }

        // Check ticker values.
        foreach ($tickers as $ticker) {
            if (strpos($ticker, ':') === false) {
                throw new \InvalidArgumentException('The ticker ' . $ticker . ' is in the wrong format');
            }
        }

        $this->_attributes['stock_tickers'] = implode(', ', $tickers);

        return $this;
    }

    public function create()
    {
        // Here we need to create a new DOMDocument. This is so we can re-import the
        // DOMElement at the other end.
        $document = new \DOMDocument;

        $news = $document->createElement('news:news');

        // Publication
        $publication = $document->createElement('news:publication');

        $news->appendChild($publication);

        // Publication attributes
        $publication->appendChild($document->createElement('news:name', $this->_publication['publication']));
        $publication->appendChild($document->createElement('news:language', $this->_publication['lang']));

        // Append attributes
        foreach ($this->_attributes as $name => $value) {
            if (null !== $value) {
                $news->appendChild($document->createElement('news:' . $name, $value));
            }
        }

        return $news;
    }

    public function root(\DOMElement & $root)
    {
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9');
    }

}
