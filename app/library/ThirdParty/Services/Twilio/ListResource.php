<?php

/**
 * Abstraction of a list resource from the Twilio API.
 *
 * @category Services
 * @package  Services_Twilio
 * @author   Neuman Vong <neuman@twilio.com>
 * @license  http://creativecommons.org/licenses/MIT/ MIT
 * @link     http://pear.php.net/package/Services_Twilio
 */
abstract class Services_Twilio_ListResource
    extends Services_Twilio_Resource
    implements IteratorAggregate
{
    private $_page;

    /**
     * Gets a resource from this list.
     *
     * @param string $sid The resource SID
     * @return Services_Twilio_InstanceResource The resource
     */
    public function get($sid)
    {
        $schema = $this->getSchema();
        $type = $schema['instance'];
        return new $type(is_object($sid)
            ? new Services_Twilio_CachingDataProxy(
                isset($sid->sid) ? $sid->sid : NULL, $this, $sid
            ) : new Services_Twilio_CachingDataProxy($sid, $this));
    }

    /**
     * Deletes a resource from this list.
     *
     * @param string $sid The resource SID
     * @return null
     */
    public function delete($sid, array $params = array())
    {
        $schema = $this->getSchema();
        $basename = $schema['basename'];
        $this->proxy->deleteData("$basename/$sid", $params);
    }

    /**
     * Create a resource on the list and then return its representation as an
     * InstanceResource.
     *
     * @param array $params The parameters with which to create the resource
     *
     * @return Services_Twilio_InstanceResource The created resource
     */
    protected function _create(array $params)
    {
        $schema = $this->getSchema();
        $basename = $schema['basename'];
        return $this->get($this->proxy->createData($basename, $params));
    }

    /**
     * Create a resource on the list and then return its representation as an
     * InstanceResource.
     *
     * @param array $params The parameters with which to create the resource
     *
     * @return Services_Twilio_InstanceResource The created resource
     */
    public function retrieveData($sid, array $params = array())
    {
        $schema = $this->getSchema();
        $basename = $schema['basename'];
        return $this->proxy->retrieveData("$basename/$sid", $params);
    }

    /**
     * Create a resource on the list and then return its representation as an
     * InstanceResource.
     *
     * @param array $params The parameters with which to create the resource
     *
     * @return Services_Twilio_InstanceResource The created resource
     */
    public function createData($sid, array $params = array())
    {
        $schema = $this->getSchema();
        $basename = $schema['basename'];
        return $this->proxy->createData("$basename/$sid", $params);
    }

    /**
     * Returns a page of InstanceResources from this list.
     *
     * @param int   $page The start page
     * @param int   $size Number of items per page
     * @param array $size Optional filters
     *
     * @return Services_Twilio_Page A page
     */
    public function getPage($page = 0, $size = 50, array $filters = array())
    {
        $schema = $this->getSchema();
        $page = $this->proxy->retrieveData($schema['basename'], array(
            'Page' => $page,
            'PageSize' => $size,
        ) + $filters);

        $page->{$schema['list']} = array_map(
            array($this, 'get'),
            $page->{$schema['list']}
        );

        return new Services_Twilio_Page($page, $schema['list']);
    }

    /**
     * Returns meta data about this list resource type.
     *
     * @return array Meta data
     */
    public function getSchema()
    {
        $name = get_class($this);
        $parts = explode('_', $name);
        $basename = end($parts);
        return array(
            'name' => $name,
            'basename' => $basename,
            'instance' => substr($name, 0, -1),
            'list' => self::decamelize($basename),
        );
    }

    public function getIterator($page = 0, $size = 50, array $filters = array())
    {
        return new Services_Twilio_AutoPagingIterator(
            array($this, 'getPageGenerator'),
            create_function('$page, $size, $filters',
                'return array($page + 1, $size, $filters);'),
            array($page, $size, $filters)
        );
    }

    public function getPageGenerator($page, $size, array $filters = array()) {
        return $this->getPage($page, $size, $filters)->getItems();
    }
}
