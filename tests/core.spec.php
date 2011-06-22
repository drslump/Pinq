<?php

include_once __DIR__ . '/../library/DrSlump/Pinq.php';


describe "Pinq"

  before
      $data = array();
      $fp = fopen(__DIR__ . '/data.csv', 'r');
      $header = fgetcsv($fp);
      while (!feof($fp)) {
          $row = fgetcsv($fp);
          if (empty($row)) continue;

          $assoc = array();
          foreach ($row as $k=>$col) {
              $assoc[$header[$k]] = $col;	
          }
          $data[] = $assoc;
      }
      $W->data = $data;
  end

  describe "From/Constructor"
    it "should support arrays"
      $p = pinq($W->data);
      count($p->toArray()) should eq (count($W->data));
    end

    it "should support iterable objects (Iterators, Traversable)"
      $it = new ArrayIterator($W->data);
      $p = pinq($it);
      count($p->toArray()) should eq (count($W->data));
    end
  end

  describe "Where/Filtering"
    it "should support checking if a property is truly"
      $p = pinq(array( array('a'=>1, 'b'=>0), array('a'=>0, 'b'=>1) ))
           ->where('a');

      count($p->toArray()) should eq 1;
    end

    it "should support checking a property against a value with an operator"
      $p = pinq($W->data)
           ->where('party', 'eq', 'Democrat');

      // 81 rows with a Democrat president
      count($p->toArray()) should eq 81;
    end

    it "should support supplying a lambda for a given property"
      $p = pinq($W->data)
           ->where('president', function($val){
	         return strpos($val, 'John ') === 0;
           });

      // 13 rows with a president named John
      count($p->toArray()) should eq 13;
    end

    it "should support supplying a lambda for an item in the collection"
      $p = pinq($W->data)
           ->where(function($itm){
	         return $itm['year'] >= 2000;
           });

      // 10 rows from year 2000
      count($p->toArray()) should eq 10;
    end

    it "should support multiple where operations"
      $p = pinq($W->data)
           ->where('party', 'eq', 'Democrat')
           ->where(function($itm){ return $itm['year'] >= 2000; });

      // 2 rows with Democrats since 2000
      count($p->toArray()) should eq 2;
    end
  end

  describe "Order/Sort"
    it "should order ascending"
      $p = pinq(array(57, 12, 43, 23, 8))
           ->order();
      $p->toArray() should equal array(8, 12, 23, 43, 57);
    end

    it "should order descending"
      $p = pinq(array(57, 12, 43, 23, 8))
           ->order(null, PINQ_DESC);
      $p->toArray() should equal array(57, 43, 23, 12, 8);
    end

    it "should order on a given property"
      $p = pinq($W->data)
           ->order('president');

      $first = current($p->getIterator());
      $first['president'] should be "Abraham Lincoln";   
    end

    it "should order with a supplied comparison lambda for a given property"
      $p = pinq(array('c', 'e', 'a', 'd', 'b'))
           ->order(function($a,$b){ return strcmp($a,$b); });

      $p->toArray() should equal array('a', 'b', 'c', 'd', 'e');
    end
  end

  describe "Limit/Pagination"
    it "should allow to skip the first X items"
      $p = pinq($W->data)
           ->limit(5, 10);

      $arr = $p->toArray();
      $first = current($arr);
      $first['year'] should be '1794';
      count($arr) should be 10;
    end

    it "should allow to reduce the set to just the next X items"
      $p = pinq($W->data)
           ->limit(0, 10);
      count($p->toArray()) should be 10;
    end
  end

  describe "Select/Projection"
    it "should return the unmodified item if no args are given"
      $p = pinq(array(1,2,3))
           ->select();
      $p->toArray() should equal array(1,2,3);
    end

    it "should allow to return a key=>value map with just the given fields"
      $p = pinq($W->data)
           ->select('year', 'president');
      $arr = $p->toArray();
      all($arr) should have the key 'year';
      all($arr) should have the key 'president';
      all($arr) should not have the key 'party';
    end

    it "should allow to supply a lambda to return a custom structure"
      $p = pinq($W->data)
           ->select(function($itm){ return $itm['year']; });

      all($p->toArray()) should be numeric;
    end
  end

end
