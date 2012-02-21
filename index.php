<?php
    // vim: set ts=4 et nu shiftwidth=4 :vim
    // Work with UTF-8 everywhere
    mb_internal_encoding("UTF-8");
    header('Content-Type: text/html; charset=UTF-8');

    // For which month should we render the table?
    $month=2;
    $year=2012;

    include('interval.php');
    include('utf.php');
?>
<!DOCTYPE html>
<!-- used to force HTML5 in the browsers -->
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>ToDo List Auswertung</title>
</head>
<body>
	<h1>Das hier ist die Auswertung</h1>


<table border="1" size="-1">
<thead bgcolor="#0000FF" style="color:#FFF;">
<tr>
<th>Projectnumber</th>
<th>Task</th><th>Worker</th>
<th>Total</th>
<?php
    for($day=1;$day<=days_in_month($month,$year);$day++) {
            $start=mktime(0,0,0,$month,$day,$year);
            $end=mktime(23,59,59,$month,$day,$year);
#            print "<th>".strftime('%d.%m.%Y %H',$start).'-'.strftime('%d.%m.%Y %H',$end)."</th>\n";
            print sprintf("<th>%02d.%02d.%4d</th>",$day,$month,$year);
    }
?>
</tr>
</thead>
<tbody>

<?php

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
            if ($this[$key]!=$value) {
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


class TupelList implements Iterator,ArrayAccess,Countable {

    private $position = 0;
    private $tupels = array();

    public function __construct() {

    }

    public function filter($filter) {
        $filtered=new TupelList();
        foreach ($this->tupels as $tupel) {
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

class MyTable implements Iterator,ArrayAccess {

    private $data = array();
    private $position = 0;
    private $value = 0;
#    private $name = null;

    public function __construct($name=null) {
            $this->name=$name;
    }

    public function set($key) {
            if (!isset($this->data[$key])) $this->data[$key]=new MyTable($key);
            return $this->data[$key];
    }

    public function add($value) {
            $this->value+=$value;
    }

    public function value($value=null) {
        if (isset($value)) {
            $this->value=$value;
        }
        return $this->value;
    }

    public function summary () {
            $inter=$this->value;
            foreach ($this->data as $name=>$child) {
                    $inter+=$child->summary();
            }
            return $inter;
    }

    public function tsum() {
            $sum=$this->summary();
            $sec=$sum % 60;
            $min=(($sum / 60) % 60);
            $std=floor($sum / 3600);
            return sprintf('%02d:%02d',$std,$min);
    }

    function count() {
        if (count($this->data)==0) return 0;
#        print $this->name.":"."\n";
        $count=1;        
        foreach($this->data as $name=>$child) {
            $count+=$child->count();
        }
       return $count;
    }

    /** ITERATOR FUNCTIONS **/
    function rewind() {
        #print "Rewind\n";
        $this->position = 0;
    }

    function current() {
        #print "Current\n";
        $keys=array_keys($this->data);
        $name=$keys[$this->position];
        return $this->data[$name];
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
        $keys=array_keys($this->data);
        return isset($keys[$this->position]);
    }


    /** ARRAY ACCESS FUNCTIONS **/
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset) {

        if ($offset=='SUM') {
                print "Calculating Summup of $this->name<br/>\n";
                $tab=new Mytable();
                foreach ($this->data as $child) {
                        
                }
                return $tab;
        }

        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

}


class MyTree implements ArrayAccess,Iterator {

    protected $position = 0;
    protected $parent;
    protected $children = array();

    public $attributes = array();


    function __construct($parent) {
        $this->parent=$parent;
    }

    public function assign($attributes) {
            $this->attributes=$attributes;
    }

    public function getparent() {
        return $this->parent;
    }
    
    /** ARRAY ACCESS FUNCTIONS **/
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->children[] = $value;
        } else {
            $this->children[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->children[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->children[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->children[$offset]) ? $this->children[$offset] : null;
    }

    /** ITERATOR FUNCTIONS **/
    function rewind() {
        $this->position = 0;
    }

    function current() {
        return $this->children[$this->position];
    }

    function key() {
        return $this->position;
    }

    function next() {
        ++$this->position;
    }

    function valid() {
        return isset($this->children[$this->position]);
    }

    /** Find object in depth **/
    function find($value,$key='ID') {
            $key=strtoupper($key);
            if (array_key_exists($key,$this->attributes) and $this->attributes[$key]==$value) return $this;
            foreach ($this->children as $child) {
                    $result=$child->find($value,$key);
                    if ($result!=null) return $result;
            }
            return null;
    }

    public function __get($name) {
        $name=strtoupper($name);
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }
        return null;
        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    public function __set($name, $value) {
        $name=strtoupper($name);
        if (array_key_exists($name, $this->attributes)) {
            $this->attributes[$name] = $value;
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __set(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    public function __isset($name) {
        $name=strtoupper($name);
        return isset($this->attributes[$name]);
    }

}

class Task extends MyTree {

    function __construct($parent) {
        parent::__construct($parent);
    }

    public function asxml() {
        $xmlrep='<TASK ';
        foreach ($this->attributes as $key => $value ) {
            $xmlrep.=$key.'="'.htmlspecialchars($value).'" ';
        }
        $xmlrep.=">\n";
        foreach ($this->children as $task) {
            $xmlrep.=$task->asxml();
        }
        $xmlrep.="</TASK>\n";
        return $xmlrep;
    }

}

class ToDoList extends MyTree {
    private $parser = NULL;
    private $taskpointer;

    public $timetable;

    private $person=NULL;
    private $allocatedby=NULL;

    public function __construct () {
        $this->parser = xml_parser_create();
        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, "tag_open", "tag_close");
        xml_set_character_data_handler($this->parser, "cdata");
    }

    public function asxml() {
        $xmlrep='<TODOLIST ';
        foreach ($this->attributes as $key => $value ) {
#            if ($key=='FILENAME') $value=basename($filename);
            if ($key=='CHECKEDOUTTO') $value='';
            if ($key=='FILEVERSION') $value++;
            $xmlrep.= $key.'="'.htmlspecialchars($value).'" ';
        }
        $xmlrep.=">\n";

        foreach ($this->children as $task) {
            $xmlrep.= $task->asxml();
        }

        $xmlrep.="</TODOLIST>";

        return $xmlrep;
    }

    private function protect_multilines ($matches) {
                $matches[2]=str_replace(array("\r\n", "\n", "\r"),array('\r\n','\n','\r'),$matches[2]);
                return ' '.$matches[1].'="'.$matches[2].'"';
    }

    public function readtimetable($filename) {
        $data=file_get_contents($filename);
        $data=mb_convert_encoding($data,'UTF-8',detect_utf_encoding($data));
        $data=substr($data,3);
        $data = str_getcsv($data, "\n"); //parse the rows
        foreach($data as &$Row) $Row = str_getcsv($Row, ";"); //parse the items in rows
        $header=array_shift($data);

        $timetable=new TupelList();
        foreach ($data as $row) {
                $entry=new Tupel();
                $entry['id']=$row[0];
                $entry['who']=$row[3];
                $entry['start']=strtotime($row[5]);
                $entry['end']=strtotime($row[4]);
                $entry['spent']=$entry['end']-$entry['start'];
                $entry['title']=$row[1];

                $task=$this->find($row[0]);
                $entry['externalid']=$task? $task->externalid : null;

                $timetable[]=$entry;
        }
        $this->timetable=$timetable;
    }

    public function readtodo($filename) {
        # Load File and change encoding to UTF-8 and protect multiline attributes, since they are not really valid XML.
        $data=file_get_contents($filename);
        $data=mb_convert_encoding($data,'UTF-8',detect_utf_encoding($data));
        $data=preg_replace_callback('/\s(.+?)\="(.*?)"/s',array($this,'protect_multilines'),$data);
        
        # Start building the task-tree with the root-node
        $this->taskpointer=$this;
        # Start the parser
        xml_parse($this->parser, $data);
    }

    public function writetodo($filename) {
        # Create an XML-Header
        $fcont='<?xml version="1.0" encoding="windows-1252" ? >'."\n";
        # Get XML-Representation of file
        $fcont.=$this->asxml();
        $file=fopen($filename,'w');
        # Write XML-Representation to file, add BOM in Front so UTF-16LE is detected properly
        fwrite($file,chr(0xFF) . chr(0xFE).mb_convert_encoding($fcont,'UTF-16LE','UTF-8'));
        fclose($file);
    }

    /** XML-Functions **/

    function tag_open($parser, $tag, $attributes) {

        foreach ($attributes as $key => $value) {
            // Convert from UTF-8 to WINDOWS-1252. Result is that we got proper UTF-8 (seems to be a problem of encoding twice)
            // additional replace multiline protection by real line breaks again
            $attributes[$key]=str_replace(array('\n','\r'),array("\n","\r"),mb_convert_encoding($value, "WINDOWS-1252", "UTF-8"));
        }

        switch ($tag) {
            case 'TODOLIST':
                $this->assign($attributes);
                break;
            case 'TASK':
                $task=new Task($this->taskpointer);
                $task->assign($attributes);
                $this->taskpointer[]=$task;
                $this->taskpointer=$task;
                break;
            case 'PERSON':
                    $this->person=$attributes;
                break;
            case 'ALLOCATEDBY':
                    $this->allocatedby=$attributes;
                break;
            default:
                print "OPEN UNKNOWN $tag!!";
                if (isset($attributes)) var_dump($attributes); 
        }

    }

    function cdata($parser, $cdata) {
      if(!trim($cdata)) return;
      print "CDATA: ";
      var_dump($parser, $cdata); 
    }

    function tag_close($parser, $tag) {
        switch ($tag) {
            case 'TODOLIST':
            case 'PERSON':
            case 'ALLOCATEDBY':
                break;
            case 'TASK':
                $this->taskpointer=$this->taskpointer->getparent();
                break;
            default:
                print "CLOSE UNKNOWN $tag!!";
                if (isset($attributes)) var_dump($attributes); 
        }
    }

} 

echo"<PRE>";
#print_r(mb_list_encodings());

$tdl = new ToDoList();
$tdl->readtodo('test/tasks.tdl');
#$tdl->writetodo('test/new.tdl');
#
$tdl->readtimetable('test/tasks_Log.csv');

print_r ($tdl->timetable->keys('externalid'));

/*
$grouptable=new MyTable();

for($day=1;$day<=days_in_month($month,$year);$day++) {
    # $time=$tdl->getsumtime($start,$end);
#    print "Calculation for $day.$month.$year\n";
#   var_export($time);
    foreach ($time as $id=>$data) {
        $task=$tdl->find($data['id']);
        $externalid='notdefined';
        if ($task!=null) {
            if (isset($task->externalid)) $externalid=$task->externalid;
        } else {
            /* Task is not present in the tasklist */
/*            print "NO TASK FOUND FOR $id  \n";
        }

        foreach($data['worker'] as $user=>$spent) {

            $grouptable->set('SUMME')->set('SUMME')->set($user)->set("$day.$month.$year")->add($spent);
            $grouptable->set('SUMME')->set('SUMME')->set("SUMME")->set("$day.$month.$year")->add($spent);
            $grouptable->set($externalid)->set("($id) ".$data['title'])->set("SUMME")->set("$day.$month.$year")->add($spent);
            $grouptable->set($externalid)->set("($id) ".$data['title'])->set($user)->set("$day.$month.$year")->add($spent);
#            print "$day.$month.$year:".$externalid.': ('.$id.') '.$data['title']."--$user:$spent--T:".$data['total']."\n";

        }
#       $fulltable->set($externalid,"$id "='sds';
    }
}
 */
#var_export($grouptable);

?>
<?php
$mdays=days_in_month($month,$year);
foreach ($tdl->timetable->keys('externalid') as $projectno) {

    $filter=new Tupel();
    $filter['externalid']=$projectno;

    $tasks=$tdl->timetable->filter($filter);

    print '<tr><td rowspan="">'.$projectno.' ('.count($tasks).') '."</td><td></td><td>".'SDSD'."</td>\n";

    /*    print_r($tasks); */
    for($day=1;$day<=$mdays;$day++) {
        $start=mktime(0,0,0,$month,$day,$year);
        $end=mktime(23,59,59,$month,$day,$year);

        $day=$tasks->filter('sdsd');

        print "<td>".$tasks->sum('spent')."</td>";
    }

    print "</tr>\n";


/*        foreach ($project as $task) {
            print '<tr><td rowspan="'.$task->count().'">'.$task->name."</td><td>SUM</td><td>".$task->tsum()."</td><td colspan='$mdays'></td>\n";
            $worker=$task['SUM'];
                print "<tr><td align='right'>".$worker->name."</td><td>".$worker->tsum()."</td>\n";
                for($day=1;$day<=$mdays;$day++) {
                        if ($worker["$day.$month.$year"]) {
                                print "<td>".$worker["$day.$month.$year"]->tsum()."</td>";
                        } else {
                                print "<td>00:00</td>";
                        }
                }
 
            foreach ($task as $worker) {
                if ($worker->name=='SUM') continue;
                $sum=$task->summary();
                print "<tr><td align='right'>".$worker->name."</td><td>".$worker->tsum()."</td>\n";
                for($day=1;$day<=$mdays;$day++) {
                        if ($worker["$day.$month.$year"]) {
                                print "<td>".$worker["$day.$month.$year"]->tsum()."</td>";
                        } else {
                                print "<td>00:00</td>";
                        }
                }
                print "</tr>\n";
            }
        }
        #    var_export ($test);
 */
}
?>
</tbody>
</table>
<pre>
<?php

foreach ($tdl as $task) {
        print $task->attributes['ID'].' '.$task->attributes['TITLE']."\n";
        foreach ($task as $subtask) {
            print '  '.$subtask->attributes['ID'].' '.$subtask->attributes['TITLE']."\n";
        }
}
echo "</PRE>";



?>
<?php

# print "Encoding: ".detect_utf_encoding('tasks.tdl')."\n";
#print "Encoding: ".mb_detect_encoding($text,'auto')."\n";

#$data=file_get_contents('test/tasks.tdl');
#$data=mb_convert_encoding($data,'UTF-8','UTF-16LE');
#print $data;

#$data=file_get_contents('test/new.tdl');
#$data=mb_convert_encoding($data,'UTF-8','UTF-16LE');
#print $data;

?>
</body>
</html>

<?php

# From: http://code.google.com/p/php-calendar/source/browse/trunk/php-calendar/includes/calendar.php
function day_of_week_start()
{
        global $phpcid;

        return get_config($phpcid, 'week_start');
}

// returns the number of days in the week before the 
//  taking into account whether we start on sunday or monday
function day_of_week($month, $day, $year)
{
        return day_of_week_ts(mktime(0, 0, 0, $month, $day, $year));
}

// returns the number of days in the week before the 
//  taking into account whether we start on sunday or monday
function day_of_week_ts($timestamp)
{
        $days = date('w', $timestamp);

        return ($days + 7 - day_of_week_start()) % 7;
}

// returns the number of days in $month
function days_in_month($month, $year)
{
        return date('t', mktime(0, 0, 0, $month, 1, $year));
}

//returns the number of weeks in $month
function weeks_in_month($month, $year)
{
        $days = days_in_month($month, $year);

        // days not in this month in the partial weeks
        $days_before_month = day_of_week($month, 1, $year);
        $days_after_month = 6 - day_of_week($month, $days, $year);

        // add up the days in the month and the outliers in the partial weeks
        // divide by 7 for the weeks in the month
        return ($days_before_month + $days + $days_after_month) / 7;
}

// return the week number corresponding to the $day.
function week_of_year($month, $day, $year)
{
        global $phpcid;

        $timestamp = mktime(0, 0, 0, $month, $day, $year);

        // week_start = 1 uses ISO 8601 and contains the Jan 4th,
        //   Most other places the first week contains Jan 1st
        //   There are a few outliers that start weeks on Monday and use
        //   Jan 1st for the first week. We'll ignore them for now.
        if(get_config($phpcid, 'week_start') == 1) {
                $year_contains = 4;
                // if the week is in December and contains Jan 4th, it's a week
                // from next year
                if($month == 12 && $day - 24 >= $year_contains) {
                        $year++;
                        $month = 1;
                        $day -= 31;
                }
        } else {
                $year_contains = 1;
        }
        
        // $day is the first day of the week relative to the current month,
        // so it can be negative. If it's in the previous year, we want to use
        // that negative value, unless the week is also in the previous year,
        // then we want to switch to using that year.
        if($day < 1 && $month == 1 && $day > $year_contains - 7) {
                $day_of_year = $day - 1;
        } else {
                $day_of_year = date('z', $timestamp);
                $year = date('Y', $timestamp);
        }

        /* Days in the week before Jan 1. */
        $days_before_year = day_of_week(1, $year_contains, $year);

        // Days left in the week
        $days_left = 8 - day_of_week_ts($timestamp) - $year_contains;

        /* find the number of weeks by adding the days in the week before
         * the start of the year, days up to $day, and the days left in
         * this week, then divide by 7 */
        return ($days_before_year + $day_of_year + $days_left) / 7;
}
?>
