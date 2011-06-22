<?php

namespace DrSlump {

    use DrSlump\Pinq;

    class Pinq implements \IteratorAggregate
    {
        const VERSION = '@package_version@';

        // Sort order direction
        const ASC = 1;
        const DESC = 2;


        /** @var \DrSlump\Iterator */
        protected $it = null;

        /**
         * Setup SPL autoloader for Pinq library classes
         *
         * @static
         * @return void
         */
        static public function autoload()
        {
            spl_autoload_register(function($class){
                $prefix = __CLASS__ . '\\';
                if (strpos($class, $prefix) === 0) {
                    // Remove vendor from name
                    $class = substr($class, strlen(__NAMESPACE__)+1);
                    // Convert namespace separator to directory ones
                    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
                    // Prefix with this file's directory
                    $class = __DIR__ . DIRECTORY_SEPARATOR . $class;

                    include($class . '.php');
                    return true;
                }

                return false;
            });
        }


        /**
         * @param array|Iterator|Traversable $iterable
         */
        public function __construct($iterable)
        {
            $this->from($iterable);
        }

        /**
         * Implements IteratorIterator
         *
         * @return Iterator
         */
        public function getIterator()
        {
            return $this->it;
        }

        /**
         * Sets a new dataset to work with
         *
         * @throws \InvalidArgumentException
         * @param  array|Iterator $items
         * @return Pinq
         */
        public function from($items)
        {
            if (is_array($items)) {
                $this->it = new \ArrayIterator($items);
            } else if ($items instanceof \Traversable) {
                $this->it = $items;
            } else {
               throw new \InvalidArgumentException('The $items argument must be an array or implement
                                                    the Iterator or Traversable interfaces.');
            }

            return $this;
        }

        /**
         * Project the set with only the given property names or a callback
         * function to modify the items in the set.
         *
         * You can pass a list of property names, an array with the property names
         * or a callback function to return the final result.
         *
         * @return Pinq
         */
        public function select()
        {
            // If no arguments were given the we have nothing to do
            if (func_num_args() === 0) {
                return $this;
            }

            $args = func_get_args();
            if (count($args) === 1 && is_array($args[0])) {
                $this->it = new Pinq\Select\Fields($this->it, $args[0]);
            } else if (count($args) > 1 || is_string($args[0])) {
                $this->it = new Pinq\Select\Fields($this->it, $args);
            } else if (is_callable($args[0])) {
                $this->it = new Pinq\Select\Callback($this->it, $args[0]);
            } else {
                throw new \Exception('Either a list of property names or a callback function is required');
            }

            return $this;
        }


        public function where($field = null, $comp = '=', $value = null)
        {
            // If no arguments given then just filter out empty items
            if (0 === func_num_args()) {
                $this->it = new Pinq\Where\NotEmpty($this->it);
                return $this;
            // If only one it should be a field name
            } else if (1 === func_num_args()) {
	            if (is_callable($field)) {
		            $this->it = new Pinq\Where\Callback($this->it, $field);
				} else {
	            	$this->it = new Pinq\Where\NotEmpty($this->it, $field);
				}
             	return $this;
	        // If only two arguments were given assume comparison is equality
            } else if (2 === func_num_args()) {
	            if (is_callable($comp)) {
		            $this->it = new Pinq\Where\Callback($this->it, $comp, $field);
		            return $this;
	            }
	
                $value = $comp;
                $comp = '=';	            
            }

            switch ($comp) {
                case '=':
                case 'eq':
                case 'equal':
                case 'equals':
                    $this->it = new Pinq\Where\Equal($this->it, $field, $value);
                    break;
                case 'regexp':
                case 'match':
                    $this->it = new Pinq\Where\Match($this->it, $field, $value);
                    break;
                default:
                    throw new \InvalidArgumentException('Comparison argument "' . $comp . '" not understood');
            }

            return $this;
        }

        /**
         * Sort the items in the set
         *
         * NOTE: Sorting large collections is slow and consumes more memory than
         *       other operations. Use it wisely.
         *
         * @param mixed $sortMethod
         * @param int $direction
         * @return Pinq
         */
        public function order($cbOrFieldOrSortType = SORT_REGULAR, $direction = self::ASC)
        {
            // To sort we need to flush the iterator into an array
            $items = iterator_to_array($this->it, false);

            if (is_string($cbOrFieldOrSortType)) {
	            $field = $cbOrFieldOrSortType;
	            $cbOrFieldOrSortType = function($a, $b) use ($field) {
		            if (is_array($a)) {
	                    $a = isset($a[$field]) ? $a[$field] : null;
	                } else if (is_object($a)) {
		                $a = isset($a->$field) ? $a->$field : null;
	                }
	
		            if (is_array($b)) {
	                    $b = isset($b[$field]) ? $b[$field] : null;
	                } else if (is_object($b)) {
		                $b = isset($b->$field) ? $b->$field : null;
	                }
	
 		            return strcmp($a, $b);
	            };
            }

            // Sort the array data
            if (is_callable($cbOrFieldOrSortType)) {
                usort($items, $cbOrFieldOrSortType);
            } else {
                // Regular sort function
	            $cbOrFieldOrSortType = is_int($cbOrFieldOrSortType) 
	                                 ? $cbOrFieldOrSortType 
	                                 : SORT_REGULAR;
                sort($items, $cbOrFieldOrSortType);
            }
            
            // Reverse the array when the direction in descending
            if ($direction === self::DESC) {
                $items = array_reverse($items);
            }

            // Build back an iterator from the sorted result
            $this->it = new \ArrayIterator($items);
            return $this;
        }

        /**
         * Limits the number of items returned
         *
         * @param int $offset
         * @param int $count
         * @return Pinq
         */
        public function limit($offset, $count = -1)
        {
            $this->it = new \LimitIterator($this->it, $offset, $count);
            return $this;
        }

        public function group($field)
        {
            $items = iterator_to_array($this->it, false);

            $dict = array();
            foreach ($items as $itm) {
                if (is_array($itm)) {
                    $key = isset($itm[$field]) ? $itm[$field] : null;
                } else if (is_object($itm)) {
                    $key = isset($itm->$field) ? $itm->$field : null;
                } else {
                    $key = $itm;
                }

                $dict[$key][] = $itm;
            }

            foreach ($dict as $k=>$items) {
                $dict[$k] = new \DrSlump\Pinq($items);
            }

            $this->it = new \ArrayIterator($dict);
            return $this;
        }

        /**
         * Concatenates more data to the current iterator
         *
         * @param array|Iterator $iterable
         * @return Pinq
         */
        public function concat($iterable)
        {
            if (is_array($iterable)) {
                $iterable = new \ArrayIterator($iterable);
            }

            $this->it = new \DrSlump\Pinq\Concat($this->it);
            $this->it->concat($iterable);

            return $this;
        }

        /**
         * Appends a new value to the iterator
         *
         * @param mixed $item
         * @return Pinq
         */
        public function append($item)
        {
            $this->concat(array($item));
            return $this;
        }

        /**
         * Remove duplicates from the set
         *
         * NOTE: On large collections this is slow and consumes more memory than
         *       other operations. Use it wisely.
         *
         * @param null|string $field
         * @return Pinq
         */
        public function distinct($field = null)
        {
            // To sort we need to flush the iterator into an array
            $items = iterator_to_array($this->it, false);

            if (NULL === $field) {
                $items = array_unique($items);
            } else {
                // Construct an array of values with the given field
                $unique = array();
                foreach ($items as $k => $itm) {
                    if (is_array($itm) && isset($itm[$field])) {
                        $unique[$k] = $itm[$field];
                    } elseif (is_object($itm) && isset($itm->$field)) {
                        $unique[$k] = $itm->$field;
                    }
                }
                // Get only the unique values (keys are kept)
                $unique = array_unique($unique);
                // Remove non-unique values from the original set
                $items = array_intersect_key($items, $unique);
            }

            $this->it = new \ArrayIterator($items);
            return $this;
        }

        public function reverse()
        {
            $items = iterator_to_array($this->it, false);
            $items = array_reverse($items);
            $this->it = new \ArrayIterator($items);
            return $this;
        }

        public function max($field = null)
        {
            $max = null;

            $items = iterator_to_array($this->it, false);
            foreach ($items as $itm) {
                if (is_array($itm)) {
                    $max = max($max, isset($itm[$field]) ? $itm[$field] : null);
                } else if (is_object($itm)) {
                    $max = max($max, isset($itm->$field) ? $itm->$field : null);
                } else {
                    $max = max($max, $itm);
                }
            }

            $this->it = new \ArrayIterator($items);
            return $max;
        }

        public function min($field = null)
        {
            $min = null;

            $items = iterator_to_array($this->it, false);
            foreach ($items as $itm) {
                if (is_array($itm)) {
                    $min = min($min, isset($itm[$field]) ? $itm[$field] : null);
                } else if (is_object($itm)) {
                    $min = min($min, isset($itm->$field) ? $itm->$field : null);
                } else {
                    $min = min($min, $itm);
                }
            }

            $this->it = new \ArrayIterator($items);
            return $min;
        }

        public function sum($field = null)
        {
            $sum = 0;

            $items = iterator_to_array($this->it, false);
            foreach ($items as $itm) {
                if (is_array($itm)) {
                    $sum += isset($itm[$field]) ? $itm[$field] : 0;
                } else if (is_object($itm)) {
                    $sum += isset($itm->$field) ? $itm->$field : 0;
                } else {
                    $sum += $itm;
                }
            }
            $this->it = new \ArrayIterator($items);
            return $sum;
        }

        public function avg($field = null)
        {
            $sum = $this->sum($field);
            $count = $this->count($field);
            return $count > 0 ? $sum / $count : 0;
        }

        public function count($field = null)
        {	        
            $count = 0;            
            $items = iterator_to_array($this->it, false);
            foreach ($items as $itm) {
                if (is_array($itm)) {
                    $count += isset($itm[$field]) ? 1 : 0;
                } else if (is_object($itm)) {
                    $count += isset($itm->$field) ? 1 : 0;
                } else {
                    $count += NULL !== $itm ? 1 : 0;
                }
            }
            $this->it = new \ArrayIterator($items);
            return $count;
        }

        public function first()
        {
            $this->it->rewind();
            return $this->it->current();
        }

        public function last()
        {
            $result = null;
            foreach ($this->it as $itm) {
                $result = $itm;
            }
            return $result;
        }



        /**
         * Convert the current iterator to an array
         *
         * @return array
         */
        public function toArray()
        {
            return iterator_to_array($this->it, false);
        }

        public function __call($name, $args)
        {
            // Convert camelCase to underscore naming
            $name = preg_replace('/([a-z])([A-Z])/', '$1_$2', $name);

            $parts = explode('_', $name);
            $action = array_shift($parts);
            switch ($action) {
                case 'where':
                case 'filter':
                    $field = array_shift($parts);
                    $operation = '=';
                    if (count($parts) > 0) {
                        $operation = strtolower(array_pop($parts));
                        switch ($operation) {
                            case 'eq':
                            case 'is':
                            case 'equal':
                                $operation = '=';
                                break;
                        }
                    }
                    return $this->where($field, $operation, count($args) ? $args[0] : 0);
                case 'select':
                    return $this->select($parts[0]);
                case 'order':
                case 'sort':
                    if (strtolower($parts[0]) === 'by') {
                        array_shift($parts);
                    }

                    // @todo: check for ASC/DESC and field name

                    return $this->order($args[0]);
                default:
                    throw new \BadMethodCallException('Method ' . $name . ' not valid.');
            }
        }

    }
}


namespace {

    const PINQ_ASC = \DrSlump\Pinq::ASC;
    const PINQ_DESC = \DrSlump\Pinq::DESC;

    /**
     * @param array|iterator $data
     * @return \DrSlump\Pinq
     */
    function pinq($data) {
        return new \DrSlump\Pinq($data);
    }

    // Register autoloader
    \DrSlump\Pinq::autoload();
}