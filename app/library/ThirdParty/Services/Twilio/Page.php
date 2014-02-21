<?php

/**
 * A representation of a page of resources.
 *
 * @category Services
 * @package  Services_Twilio
 * @author   Neuman Vong <neuman@twilio.com>
 * @license  http://creativecommons.org/licenses/MIT/ MIT
 * @link     http://pear.php.net/package/Services_Twilio
 */ 
class Services_Twilio_Page
    implements IteratorAggregate
{

    /**
     * The page object.
     *
     * @var object $page
     */
    protected $page;

    /**
     * The item list.
     *
     * @var array $items
     */
    protected $items;

    /**
     * Constructs a page.
     *
     * @param object $page The page object
     * @param string $name The key of the item list
     */
    public function __construct($page, $name)
    {
        $this->page = $page;
        $this->items = $page->{$name};
    }

    /**
     * The item list of the page.
     *
     * @return array A list of instance resources
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Magic method to allow retrieving the properties of the wrapped page.
     *
     * @param string $prop The property name
     *
     * @return mixed Could be anything
     */
    public function __get($prop)
    {
        return $this->page->$prop;
    }

    /**
     * Implementation of IteratorAggregate::getIterator().
     *
     * @return Traversable
     */
    public function getIterator()
    {
        return $this->getItems();
    }
}

