<?php

require_once 'library/DrSlump/Pinq.php';

$json = file_get_contents('http://search.twitter.com/search.json?q=cool');
$data = json_decode($json);

$ids = array( array('id'=>1), array('id'=>2) );

$p = pinq($data->results)
     ->concat($data->results)

     ->where('text', 'match', '/cool/')

     ->order(function($a,$b){
                $a = strtotime($a->created_at);
                $b = strtotime($b->created_at);
                return $a - $b;
       })

     ->distinct('id')

     ->limit(0, 10)
     ->select('id', 'from_user', 'text', 'created_at');

foreach ($p as $k=>$itm) {
    echo $k . ': [' . $itm['id'] . '] @' . $itm['from_user'] . ': ' . $itm['text'] . PHP_EOL;
}

//var_dump($p->toArray());

$data = array('foo', 'bar', 'baz');
$p = pinq($data)
     ->where(null, 'match', '/^b/')
     ->order(function($a,$b){ return strcmp($a,$b); }, PINQ_ASC)
     ->limit(0, 2)
     ->select(function($itm){ return strtoupper($itm); });

$a = $p->toArray();
var_dump($a);

$data = array('foo', 'bar', 'baz');
$p = pinq($data)
     ->order(SORT_REGULAR, PINQ_ASC)
     ->select(function($itm){ return strtoupper($itm); });

$a = $p->toArray();
var_dump($a);