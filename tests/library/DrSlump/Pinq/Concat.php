<?php

namespace DrSlump\Pinq;

class Concat implements \Iterator
{
    /** @var Iterator[] Holds the list of concatenated iterators */
    protected $iterators = array();

    /** @var int Current iterator being used */
    protected $current = 0;


    /**
     * @param Iterator $iterator
     */
    public function __construct($iterator = null)
    {
        if ($iterator instanceof \Iterator) {
            $this->concat($iterator);
        }
    }

    /**
     * Concatenates a new iterator to the concatenation
     *
     * @param Iterator $iterator
     */
    public function concat(\Iterator $iterator)
    {
        $this->iterators[] = $iterator;
    }

    /**
     * Get the currently active iterator
     * @return \Iterator
     */
    public function getIterator()
    {
        return isset($this->iterators[$this->current])
               ? $this->iterators[$this->current]
               : null;
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return $this->getIterator()->current();
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return scalar scalar on success, integer
     * 0 on failure.
     */
    public function key()
    {
        return $this->getIterator()->key();
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        if (!$this->getIterator()) return;

        $this->getIterator()->next();
        // If not valid jump to the next iterator
        if (!$this->getIterator()->valid()) {
            $this->current++;
            // If there is one available rewind it
            if ($this->getIterator()) {
                $this->getIterator()->rewind();
            }
        }
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->current = 0;
        if ($this->getIterator()) {
            $this->getIterator()->rewind();
        }
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        if (!$this->getIterator()) {
            return false;
        }

        // Find the next iterator with something available
        while (!$this->getIterator()->valid()) {
            $this->current++;
            if (!$this->getIterator()) {
                return false;
            }
        }

        return true;
    }
}


