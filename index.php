<?php
    // Work with UTF-8 everywhere
    mb_internal_encoding("UTF-8");
    header('Content-Type: text/html; charset=UTF-8');

    // For which month should we render the table?
    $month=2;
    $year=2012;
?>
<!DOCTYPE html>
<!-- used to force HTML5 in the browsers -->
<!-- vim: set ts=4 et nu :vim -->
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
<th>Task/Worker</th>
<th>Total<th>
<?php
    for($day=1;$day<=days_in_month($month,$year);$day++) {
            $start=mktime(0,0,0,$month,$day,$year);
            $end=mktime(23,59,59,$month,$day,$year);
            print "<th>".strftime('%d.%m.%Y %H',$start).'-'.strftime('%d.%m.%Y %H',$end)."</th>\n";
    }
?>
</tr>
</thead>
<tbody>
<tr>

<td>K-01-91716022-4500</td>
<td></td>
</tr>

<tr><td></td><td>task1</td></tr>
<tr bgcolor="#88F"><td></td><td align="right">tdo366</td></tr>
<tr bgcolor="#88f"><td></td><td align="right">tdo123</td></tr>
<tr bgcolor="#88f"><td></td><td align="right">tdo456</td></tr>
<tr><td></td><td>task2</td></tr>
<tr><td></td><td>task3</td></tr>
<tr><td></td><td>task4</td></tr>

</tbody>
</table>

<?php

// Unicode BOM is U+FEFF, but after encoded, it will look like this.
define ('UTF32_BIG_ENDIAN_BOM'   , chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF));
define ('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00));
define ('UTF16_BIG_ENDIAN_BOM'   , chr(0xFE) . chr(0xFF));
define ('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE));
define ('UTF8_BOM'               , chr(0xEF) . chr(0xBB) . chr(0xBF));

// This function tries to detect the encoding first by reading the BOM. If this fails we fallback to the build in mb_detect_enconding
function detect_utf_encoding($text) {
    $first2 = substr($text, 0, 2);
    $first3 = substr($text, 0, 3);
    $first4 = substr($text, 0, 3);
    if ($first3 == UTF8_BOM) return 'UTF-8';
    elseif ($first4 == UTF32_BIG_ENDIAN_BOM) return 'UTF-32BE';
    elseif ($first4 == UTF32_LITTLE_ENDIAN_BOM) return 'UTF-32LE';
    elseif ($first2 == UTF16_BIG_ENDIAN_BOM) return 'UTF-16BE';
    elseif ($first2 == UTF16_LITTLE_ENDIAN_BOM) return 'UTF-16LE';
    return mb_detect_encoding($text);
}

class MyTable {

    private $data = array();

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

    private $timetable;

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

    public function getsumtime($starttime=null,$endtime=null,$who=null) {
            $result=array();            
            foreach ($this->timetable as $runid => $data) {
                $title=$data['title'];
                foreach ($data['time'] as $entry) {
                    if (isset($who) and $entry['who']!=$who) continue;
                    $start=$entry['start'];
                    $end=$entry['end'];
                    // Crop START and END-Time to the given limits (e.g. when do calculation > 1 day)
                    if (isset($starttime) and $start<$starttime) $start=$starttime;
                    if (isset($endtime) and $end>$endtime) $end=$endtime;
                    if (($end-$start)<=0) continue;
                    if (! isset($result[$runid])) $result[$runid]=array();
                    $result[$runid]['title']=$title;
                    $result[$runid]['id']=$runid;
                    if (! isset($result[$runid]['worker'])) $result[$runid]['worker']=array();
                    if (! isset($result[$runid]['total'])) $result[$runid]['total']=0;
                    if (! isset($result[$runid]['worker'][$entry['who']])) $result[$runid]['worker'][$entry['who']]=0;
                    $result["$runid"]['total']+=($end-$start);
                    $result["$runid"]['worker'][$entry['who']]+=($end-$start);
                    # print "$runid: ".$entry['who'].' '.$entry['start'].'-'.$entry['end']."\n";
                }
            }
#           var_export($result); 
            return $result;
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

        $timetable=array();
        foreach ($data as $row) {
                $id=$row[0];
                $entry['who']=$row[3];
                $entry['start']=strtotime($row[5]);
                $entry['end']=strtotime($row[4]);
                $entry['spent']=$entry['end']-$entry['start'];
                $timetable[$id]['title']=$row[1];
                $timetable[$id]['time'][]=$entry;
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
        $fcont='<?xml version="1.0" encoding="windows-1252" ?>'."\n";
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

$fulltable=new MyTable();

for($day=1;$day<=days_in_month($month,$year);$day++) {
    $start=mktime(0,0,0,$month,$day,$year);
    $end=mktime(23,59,59,$month,$day,$year);
    $time=$tdl->getsumtime($start,$end);
    print "Calculation for $day.$month.$year\n";
#   var_export($time);
    foreach ($time as $id=>$data) {
        $task=$tdl->find($data['id']);
        $externalid='notdefined';
        if ($task!=null) {
            if (isset($task->externalid)) $externalid=$task->externalid;
        } else {
            /* Task is not present in the tasklist */
            print "NO TASK FOUND FOR $id  \n";
        }
        foreach($data['worker'] as $user=>$spent) {
            print "$day.$month.$year:".$externalid.': ('.$id.') '.$data['title']."--$user:$spent--T:".$data['total']."\n";



        }

#       $fulltable->set($externalid,"$id "='sds';
    }
}
var_export ($fulltable);

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
