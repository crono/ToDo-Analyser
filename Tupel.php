<?php
    // vim: set ts=4 et nu shiftwidth=4 :vim
class Tupel implements ArrayAccess,Iterator {
    private $fields;
    private $position=0;

    public function __construct() {
    }
    
    public function set($field,$value) {
        $this->fields[$field]=$value;
    }

    public function match(Tupel $comp) {
        foreach($comp as $key=>$value) {
            if ($value instanceof Comparable) {
                if ($this[$key]->compare($value)) return true;
                return false;
            } else if ($this[$key]!=$value) {
                return false;
            }
        }
        return true;
    }

    /** ARRAY ACCESS FUNCTIONS **/
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->fields[] = $value;
        } else {
            $this->fields[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->fields[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->fields[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->fields[$offset]) ? $this->fields[$offset] : null;
    }

    /** ITERATOR FUNCTIONS **/
    function rewind() {
#        print "Rewind\n";
        $this->position = 0;
    }

    function current() {
 #       print "Current\n";
        $keys=array_keys($this->fields);
        $key=$keys[$this->position];
        return $this->fields[$key] ? $this->fields[$key] : null;
    }

    function key() {
#        print "Key\n";
        $keys=array_keys($this->fields);
        $key=$keys[$this->position];
        return $key;
    }

    function next() {
#        print "Next\n";
        ++$this->position;
    }

    function valid() {
        $keys=array_keys($this->fields);
        return isset($keys[$this->position]);
    }
    
}

?>
