<?php

namespace DrSlump\Pinq\Select;

class Fields extends \IteratorIterator
{
    /** @var array */
    protected $fields = array();

    /**
     * Wraps a traversable interface to report back only the given fields
     *
     * @param \Traversable $it
     * @param array $fields
     */
    public function __construct(\Traversable $it, array $fields)
    {
        parent::__construct($it);
        $this->fields = $fields;
    }

    /**
     * Filters each item in the iteration to only contain the configured
     * fields.
     *
     * @return array
     */
    public function current()
    {
        $itm = parent::current();
        $result = array();

        if (is_array($itm)) {
            foreach ($this->fields as $field) {
                $result[$field] = isset($itm[$field]) ? $itm[$field] : null;
            }
        } else if (is_object($itm)) {
            foreach ($this->fields as $field) {
                $result[$field] = isset($itm->$field) ? $itm->$field : null;
            }
        } else {
            foreach ($this->fields as $field) {
                $result[$field] = null;
            }
        }

        return $result;
    }
}
