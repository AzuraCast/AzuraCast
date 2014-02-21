<?php

class Services_Twilio_AutoPagingIterator
    implements Iterator
{
    protected $generator;
    protected $promoter;
    protected $args;
    protected $items;

    private $_args;

    public function __construct($generator, $promoter, array $args) {
        $this->generator = $generator;
        $this->promoter = $promoter;
        $this->args = $args;
        $this->items = array();

        // Save a backup for rewind()
        $this->_args = $args;
    }

    public function current()
    {
        return current($this->items);
    }

    public function key()
    {
        return key($this->items);
    }

    public function next()
    {
        try {
            $this->loadIfNecessary();
            return next($this->items);
        }
        catch (Services_Twilio_RestException $e) {
            // Swallow the out-of-range error
        }
    }

    public function rewind()
    {
        $this->args = $this->_args;
        $this->items = array();
    }

    public function count()
    {
        throw new BadMethodCallException('Not allowed');
    }

    public function valid()
    {
        try {
            $this->loadIfNecessary();
            return key($this->items) !== null;
        }
        catch (Services_Twilio_RestException $e) {
            // Swallow the out-of-range error
        }
        return false;
    }

    protected function loadIfNecessary()
    {
        if (// Empty because it's the first time or last page was empty
            empty($this->items)
            // null key when the items list is iterated over completely
            || key($this->items) === null
        ) {
            $this->items = call_user_func_array($this->generator, $this->args);
            $this->args = call_user_func_array($this->promoter, $this->args);
        }
    }
}
