<pre>
<?php
// vim: set ts=4 et nu shiftwidth=4 :vim
//

$i1=new Interval(10,null);
$i2=new Interval(5,30);
$i3=new Interval(null,null);

$i1->intersect($i3);
$i2->intersect($i3);

print_r($i1);
print_r($i2);
print_r($i3);

print "Dauer: ".$i1->duration();
print "Dauer: ".$i1->duration().' '.$i1->start.' '.$i1->end.' '.$i1->sectstart.' '.$i1->sectend;

$i3->set(8,15);
print_r($i1);
print_r($i2);
print_r($i3);

print "Dauer: ".$i1->duration().' '.$i1->start.' '.$i1->end.' '.$i1->sectstart.' '.$i1->sectend;

class Interval {

    protected $start=null; /* Open Interval */
    protected $end=null;   /* Open Interval */

    protected $intersect=null;

    public function __construct($start,$end) {
        $this->set($start,$end);
    }

    public function set($start,$end) {
        /* If one of the values is null we need to handle open Intervals */
        if (is_null($start) or is_null($end)) {
            $this->start=$start;
            $this->end=$end;
        } else {
            /* we guarantee that the start value is lower than the end value */
            $this->start=($start<$end) ? $start : $end;
            $this->end=($start<$end) ? $end : $start;
        }
    }

    public function duration() {
        $a=$this->start;
        $b=$this->end;
        $i=null;
        $j=null;
        if (is_null($a) or is_null($b)) return 0;

        if (isset($this->intersect)) {
            $i=$this->intersect->start;
            $j=$this->intersect->end;
        }
        /* If we should intersect with open-intervals, we use boundaries of current interval */
        $i=isset($i) ? $i : $a;
        $j=isset($j) ? $j : $b;

        $start=($i>$a) ? $i : $a;
        $end=($j<$b) ? $j : $b;

        return ($end>$start) ? ($end-$start) : 0;
    }

    public function intersect(Interval $interval) {
        $this->intersect=$interval;
    }

    public function nointersect() {
        $this->intersect=null;
    }

    public function sectstart() {
        $a=$this->start;
        $b=$this->end;
        $i=null;
        $j=null;

        if (isset($this->intersect)) {
            $i=$this->intersect->start;
            $j=$this->intersect->end;
        }

        /* If we should intersect with open-intervals, we use boundaries of current interval */
        $i=isset($i) ? $i : $a;
        $j=isset($j) ? $j : $b;

        print "compare: $a:$b(sect)$i:$j\n";

        

    }

    public function sectend() {
        $a=$this->start;
        $b=$this->end;
        $i=null;
        $j=null;

        if (isset($this->intersect)) {
            $i=$this->intersect->start;
            $j=$this->intersect->end;
        }

        /* If we should intersect with open-intervals, we use boundaries of current interval */
        $i=isset($i) ? $i : $a;
        $j=isset($j) ? $j : $b;

        print "compare: $a:$b(sect)$i:$j\n";
    }

    public function __get($name) {
        print 'Get: '.$name."\n";
        if ($name=='start') return $this->start;
        if ($name=='end') return $this->end;
        if ($name=='sectstart') return $this->sectstart();
        if ($name=='sectend') return $this->sectend();
    }


}

?>
</pre>
