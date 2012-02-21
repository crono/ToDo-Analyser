<pre>
<?php
// vim: set ts=4 et nu shiftwidth=4 :vim
//

class Interval {

    protected $start=null; /* Open Interval */
    protected $end=null;   /* Open Interval */

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

        if (is_null($a) or is_null($b)) return null;    // We don't know the size of the interval since it is infinite */

        return ($b>$a) ? ($b-$a) : null; // If end is smaller than start there is an error in the interval 
    }

    public function intersect(Interval $interval) {
        # print "Start ".$this->start.":".$this->end." intersect ".$interval->start.":".$interval->end;

        $a = $this->start;
        $b = $this->end;

        $c = $interval->start;
        $d = $interval->end;

        $start=$a;
        $end=$b;

        if (is_null($a)) {
            $start=$c;
        } elseif (isset($c)) {
            $start=($a>$c) ? $a : $c;
        }

        if (is_null($b)) {
            $end=$d;
        } elseif (isset($d)) {
            $end=($b<$d) ? $b : $d;
        }

        # print " = ".$start.":".$end."\n";
        if (isset($start)and isset($end) and ($start>$end)) {
            return null;
        }

        return new Interval($start,$end);
    }

    
    public function __get($name) {
        if ($name=='start') return $this->start;
        if ($name=='end') return $this->end;
    }

    public function __tostring() {
        return $this->start.':'.$this->end;
    }

}

# Is only executed if startet as main php-script
if (!debug_backtrace()) {

    $i1=new Interval(10,null);
    $i2=new Interval(5,30);
    $i3=new Interval(null,null);
    $i4=new Interval(null,20);
    $i5=new Interval(null,1);

    $r1=$i1->intersect($i3);
    $r2=$i2->intersect($i3);
    $r3=$i4->intersect($i3);
    $r4=$i5->intersect($i3);
    #$i2->intersect(array(10,20));

    $i3->set(2,15);
    $r1=$i1->intersect($i3);
    $r2=$i2->intersect($i3);
    $r3=$i3->intersect($i3);
    if ( $r4=$i4->intersect($i3)) {
        print "Hurra! $r4\n";
    };
    if (! $r5=$i5->intersect($i3)) {
        print "Hurra! $r5\n";
    };
}

?>
</pre>
