<?php
    // vim: set ts=4 et nu shiftwidth=4 :vim


class TupelList implements Iterator,ArrayAccess,Countable {

    private $position = 0;
    private $tupels = array();

    public function __construct() {

    }

    public function filter(Tupel $filter) {
        $filtered=new TupelList();
        foreach ($this->tupels as $tupel) {
            if (!($tupel instanceof Tupel)) print $filter;
            if ($tupel->match($filter)) {
                $filtered[]=$tupel;
            }
        }
#        print_r($filtered);
        return $filtered;
    }

    public function sum($field) {
        $sum=0;
        foreach ($this->tupels as $tupel) {
            if ($tupel[$field] instanceof Calculable) {
                $sum+=$tupel[$field]->calcvalue();
            } else {
                $sum+=$tupel[$field];
            }
        }
        return $sum;
    }

    public function keys($field) {
        $list=array();
        foreach ($this->tupels as $tupel) {
            $list[]=$tupel[$field];
        }
        return array_unique($list,SORT_STRING);
    }

    public function find($field,$value) {
        foreach ($this->tupels as $tupel) {
            if ($tupel[$field]==$value) return $tupel;
        }
        return null;
    }

    /** ARRAY ACCESS FUNCTIONS **/
    public function offsetSet($offset, $tupel) {
        if (is_null($offset)) {
            $this->tupels[]=$tupel;
        } else {
            $this->tupels[$offset]=$tupel;
        }
    }

    public function offsetExists($offset) {
        return isset($this->tupels[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->tupels[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->tupels[$offset]) ? $this->tupels[$offset] : null;
    }

    /** ITERATOR FUNCTIONS **/
    function rewind() {
        #print "Rewind\n";
        $this->position = 0;
    }

    function current() {
        #print "Current\n";
        return $this->tupels[$this->position];
    }

    function key() {
        #print "Key\n";
        return $this->position;
    }

    function next() {
        #print "Next\n";
        ++$this->position;
    }

    function valid() {
        #print "Valid\n";
        return isset($this->tupels[$this->position]);
    }

    /** COUNTABLE FUNCTIONS **/
    public function count ( ) {
        return count($this->tupels);
    }
}

?>
