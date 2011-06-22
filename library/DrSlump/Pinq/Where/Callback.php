<?php

namespace DrSlump\Pinq\Where;

class Callback extends \FilterIterator
{
	/** @var callback */
	protected $callback = null;
    /** @var string field name */
    protected $field = null;

    /**
     * @param \Traversable $it
     * @param string $field
     */
    public function __construct(\Traversable $it, $callback, $field = NULL)
    {
        parent::__construct($it);
        $this->callback = $callback;
        $this->field = $field;
    }

    /**
     * Check whether the current element of the iterator is acceptable
     *
     * @link http://php.net/manual/en/filteriterator.accept.php
     * @return bool true if the current element is acceptable, otherwise false.
     */
    public function accept()
    {
        $itm = $this->getInnerIterator()->current();

        if (NULL !== $this->field) {
            if (is_array($itm)) {
                $itm = isset($itm[$this->field]) ? $itm[$this->field] : null;
            } else if (is_object($itm)) {
                $itm = isset($itm->{$this->field}) ? $itm->{$this->field} : null;
            } else {
                $itm = null;
            }
        }

        return call_user_func($this->callback, $itm);
    }
}


