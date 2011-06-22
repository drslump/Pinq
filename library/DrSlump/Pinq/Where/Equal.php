<?php

namespace DrSlump\Pinq\Where;

class Equal extends \FilterIterator
{
    /** @var string field name */
    protected $field = null;
    /** @var mixed value to check against */
    protected $value = null;

    /**
     * @param \Traversable $it
     * @param string $field
     * @param string $value
     */
    public function __construct(\Traversable $it, $field, $value)
    {
        parent::__construct($it);
        $this->field = $field;
        $this->value = $value;
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

        return $itm == $this->value;
    }
}


