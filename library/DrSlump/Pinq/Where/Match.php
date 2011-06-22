<?php

namespace DrSlump\Pinq\Where;

class Match extends \FilterIterator
{
    /** @var string field name */
    protected $field = null;
    /** @var string regular expression */
    protected $regexp = null;

    /**
     * @param \Traversable $it
     * @param string $field
     * @param string $regexp
     */
    public function __construct(\Traversable $it, $field, $regexp)
    {
        parent::__construct($it);
        $this->field = $field;
        $this->regexp = $regexp;
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

        return is_string($itm)
               ? preg_match($this->regexp, $itm)
               : false;
    }
}


