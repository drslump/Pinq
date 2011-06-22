<?php

namespace DrSlump\Pinq\Select;

class Callback extends \IteratorIterator
{
    /** @var callback */
    protected $callback = null;

    /**
     * Wraps a traversable interface to report back only the given fields
     *
     * @param \Traversable $it
     * @param callback $callback
     */
    public function __construct(\Traversable $it, $callback)
    {
        parent::__construct($it);
        $this->callback = $callback;
    }

    /**
     * Filters each item in the iteration to become the result of a callback
     * function.
     *
     * @return mixed
     */
    public function current()
    {
        $cb = $this->callback;
        $itm = parent::current();

        if ($cb instanceof \Closure) {
            return $cb($itm);
        } else {
            return call_user_func($cb, $itm);
        }
    }
}

