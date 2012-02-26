<?php
    // vim: set ts=4 et nu shiftwidth=4 :vim
    // Work with UTF-8 everywhere
    mb_internal_encoding("UTF-8");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Datum in der Vergangenheit
    header('Content-Type: text/html; charset=UTF-8');

function str_getcsv2($input, $delimiter=',', $enclosure='"', $escape=null, $eol=null) {
    return preg_split("/$delimiter+/",$input);
}


    // For which month should we render the table?
    $month=isset($_POST['month']) ? (int)$_POST['month'] : date('m');
    $year=isset($_POST['year']) ? (int)$_POST['year'] : date('Y');
    $dmode=isset($_POST['mode']) ? $_POST['mode'] : 'HM';

    $fid=0;
    if (isset($_FILES['userfile'])):
    foreach ($_FILES['userfile']['name'] as $fname) {
        if ($_FILES['userfile']['error'][$fid]==0) {
            $fname=basename(strtolower($fname));
            if (substr($fname,-4)=='.tdl') {
                move_uploaded_file($_FILES['userfile']['tmp_name'][$fid],"data/$fname");
                $tdlfile=$fname;
            }
            if (substr($fname,-4)=='.csv') {
                move_uploaded_file($_FILES['userfile']['tmp_name'][$fid],"data/$fname");
                $csvfile=$fname;
            }
        }
        $fid++;
    }
    endif ;
    if (!isset($csvfile)) {
        $csvfile=isset($_POST['csvfile']) ? basename($_POST['csvfile']) : '';
    }
    if (!isset($tdlfile)) {
        $tdlfile=isset($_POST['tdlfile']) ? basename($_POST['tdlfile']) : '';
    }


    $prjnos=array(
        '' => array('UNKNOWN','UNKNOWN'),
        'K-01-91716066-A1SD' => array('Maintenance for QIP','Process Development'),
        'K-01-91716066-A1SE' => array('Maintenance for QIP','Process Development'),
        'K-01-91716023' => array('IT for JOIN 2012',''),
        'K-01-91716023-8000' => array('IT for JOIN 2012','AD/System Infrastructure/ LEME'),
        'K-01-91716023-7000' => array('IT for JOIN 2012','AD/System Infrastructure/ OTHER'),
        'K-01-91716023-6000' => array('IT for JOIN 2012','AD/System Infrastructure/ SFPC'),
        'K-01-91716023-5000' => array('IT for JOIN 2012','AD/System Infrastructure/ LEH'),
        'K-01-91716023-4000' => array('IT for JOIN 2012','AD/System Infrastructure/ LED'),
        'K-01-91716023-3000' => array('IT for JOIN 2012','AD/System Infrastructure/ LPTI'),
        'K-01-91716023-2000' => array('IT for JOIN 2012','AD/System Infrastructure/ LKCA'),
        'K-01-91716023-1000' => array('IT for JOIN 2012','AD/System Infrastructure/ LBPP'),
        'K-01-91716022-9500' => array('Wartung ITI 2012','Architektur'),
        'K-01-91716022-9000' => array('Wartung ITI 2012','div. Applikationen'),
        'K-01-91716022-7000' => array('Wartung ITI 2012','Site Management'),
        'K-01-91716022-4500' => array('Wartung ITI 2012','WebServer'),
        'K-01-91716022-4200' => array('Wartung ITI 2012','WinServer Troubleshooting'),
        'K-01-91716022-4000' => array('Wartung ITI 2012','WinServer Auf/Abbau Migration'),
        'K-01-91716022-2000' => array('Wartung ITI 2012','SMS'),
        'K-01-91715964-A1SE' => array('Riverbed WAN-accelerator','Process Development'),
        'K-01-91715876-A1SB' => array('Transfer of DNS/DHCP','Process Development'),
        'K-01-91715721-A1SE' => array('ProCorr New Server','Application Development'),
        'K-01-91715496-A1SE' => array('Maintenance CFD-server','Application Development'),
        'K-01-8850ADCT-XBST' => array('Queensland LNG T1','Baustellenbetrieb/-abwicklung'),
        'K-01-3140AJ7J-A1SD' => array('Novy Urengoy III-OS','Information Technology'),
        'K-01-3130AGAD-A1SD' => array('AL JUBAIL_08','Information Technology'),
        'K-01-3110A9NK-A1SD' => array('DAHEJ','Information Technology'),
        'K-01-2410ACH7-A1SD' => array('SECUNDA-55','Information Technology'),
        'K-01-2410ACH6-A1SD' => array('SECUNDA-59','Information Technology'),
        'K-01-1110AEF3-A1SD' => array('REGHAIA','Information Technology'),
        'K-01-1110A05M-A2SD' => array('Pearl Qatar','Informationstechnik'),
        'K-01-3110A126-A1SD' => array('Wesseling','Information Technology'),
        'K-01-3920ABJ5-A1SD' => array('Al Jubail AA','Information Technology'),
        'K-01-1510AKC7-A1SD' => array('Cilegon','Information Technology'),
        'K-01-3920A13X-A1SD' => array('Sasolburg_02','Information Technology'),
        'K-01-2120AEYD-A1SD' => array('SECUNDA-63','Information Technology'),
        'K-01-8850ADCT-A1SD' => array('Tarragona','Information Technology'),
        'K-01-3111A2UN-A1SD' => array('El Tablazo','Information Technology'),
        'K-01-1510AMC3-A1SD' => array('Map Ta Phut','Information Technology'),
        'K-01-1110A61D-A1SD' => array('Mirfa','Information Technology'),
        'K-01-3110AB52-A1SD' => array('Ruwais 3','Information Technology'),
        '----DEFAULT----' => array('',''),
#        'K-01--A1SD' => array('Milazzo','Information Technology');
#        'K-01--A1SD' => array('Temirtau','Information Technology');
    );

    include_once('math.php');
    include_once('interval.php');
    include_once('utf.php');
    include_once('Tupel.php');
    include_once('TupelFilter.php');
    include_once('TupelList.php');

function prefix_lookup($key,$data) {

    if (isset($data[$key])) {
        return $data[$key];
    }

    return $data['----DEFAULT----'] ? $data['----DEFAULT----'] : '' ;
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

    function field($fieldname,$value,$key='ID') {
        $obj=$this->find($value,$key);
        if (is_null($obj)) return;
        return $obj->attributes[$fieldname];
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

    public function parentchain($field='ID') {
        # print "Get-Parent: ".$this->ID."\n";
        if ($this->parent instanceof Task) {
            # print "query parent\n";
            $blub=$this->parent->parentchain($field);
            $blub[]=$this->parent->$field;
            return $blub;
        } else {
            # print "no parent\n";
            return array();
        }
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
        $data = str_getcsv2($data,"\n"); //parse the rows
        foreach($data as &$Row) $Row = mb_split(";",$Row);#, ";"); //parse the items in rows
        $header=array_shift($data);

        $timetable=new TupelList();
        foreach ($data as $row) {
                if ($row[0]=='') continue;
                $entry=new Tupel();
                $entry['id']=$row[0];
                $entry['who']=isset($row[3]) ? $row[3] : print "<h2>FEHLER:-".var_export($row,true)."-</h2>";
                $entry['start']=isset($row[5]) ? strtotime($row[5]) : print "<h2>FEHLER:-".var_export($row,true)."-</h2>";
                $entry['end']=strtotime($row[4]);
                $entry['time']=new Interval(strtotime($row[5]),strtotime($row[4]));
                $entry['spent']=$entry['end']-$entry['start'];
                $entry['title']=$row[1];

                $task=$this->find($row[0]);
                $entry['externalid']=$task ? $task->externalid : null;
                $entry['l2task']=$task ? $task : null;

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
?>
<!DOCTYPE html>
<!-- used to force HTML5 in the browsers -->
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>ToDo List Auswertung</title>
<style>
table {
    font-size: small;
}

.weekend {
    background:#AAA;
}

.projectrow {
    background:#99F;
}
</style>
<script>
function viewinfo ($task) {
    console.log("View Task: "+$task);
}
</script>
</head>
<body>
    <h1>Settings</h1>
    <form method='POST' enctype="multipart/form-data">
        Monat:<input type="text" name="month" value="<?= $month ?>"></br>
        Jahr:<input type="text" name="year" value="<?= $year ?>"></br>
        Modus:<select name="mode">
                <option value="H,">hour fractional</option>
                <option value="P">percentage</option>
                <option value="HM">HH:MM</option>
                <option value="HMS">HH:MM:SS</option>
                <option value="S">SSSS</option>
                <option value="S:S">THIS:TOTAL</option>
              </select></br>
        <input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
        Choose a TDL-file<select name="tdlfile" style="width: 100px" >
<?php foreach ( glob('data/*.tdl') as $tdl): $fname=basename($tdl); 
    if ($fname == $tdlfile) : ?>
        <option value="<?= $fname ?>" selected="selected"><?= $fname ?></option>
    <? else: ?>
        <option value="<?= $fname ?>"><?= $fname ?></option>
    <? 
    endif;        
endforeach ?>
        </select> or upload a new: <input name="userfile[]" type="file"><br>
        Choose a CSV-file<select name="csvfile" style="width: 100px" >
                <?php
foreach ( glob('data/*.csv') as $csv): $fname=basename($csv) ;
    if ($fname == $csvfile) : ?>
        <option value="<?= $fname ?>" selected="selected"><?= $fname ?></option>
    <? else: ?>
        <option value="<?= $fname ?>"><?= $fname ?></option>
    <? 
    endif;        
endforeach ?>
              </select> or upload a new: <input name="userfile[]" type="file"><br>
        <input type="submit" name="enter" value="go...">
    </form>

    <h1>Summary <?php print sprintf('%02d.%04d',$month,$year) ?></h1>

<?php
echo"<PRE>";
#print_r(mb_list_encodings());

if (! (isset($tdlfile) and isset($csvfile))) {
    die;
}
if (! (file_exists('data/'.$tdlfile) and  file_exists('data/'.$csvfile))) {
    die;
}

$tdl = new ToDoList();
$tdl->readtodo('data/'.$tdlfile);
$tdl->readtimetable('data/'.$csvfile);

#$tdl->writetodo('test/new.tdl');
# print_r ($tdl->timetable->keys('externalid'));

?>
</PRE>
<table>
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
            $dow=date('w',$start);
            $addclass='';
            if (($dow==0) or ($dow==6)) $addclass='weekend';
            print sprintf("<th class='%s'>%02d.%02d</th>",$addclass,$day,$month);
    }
?>
</tr>
</thead>
<tbody>


<?php

$mdays=days_in_month($month,$year);

$monthfilter=new TupelFilter();
$monthfilter['time']=new Interval(mktime(0,0,0,$month,1,$year),mktime(23,59,29,$month,$mdays,$year));

$monthtasks=$tdl->timetable->filter($monthfilter);

$dailytotal["$month.$year"]=$monthtasks->sum('spent');

print "<tr><td>SUM ".sprintf('%02d.%04d',$month,$year)."</td><td>&nbsp;</TD><td>&nbsp;</TD><td>".sectostr($dailytotal["$month.$year"],'HM')."&nbsp;</td>";
/*    print_r($tasks); */
for($day=1;$day<=$mdays;$day++) {
    $filter=new TupelFilter();
    $filter['time']=new Interval(mktime(0,0,0,$month,$day,$year),mktime(23,59,59,$month,$day,$year));

    $res=$monthtasks->filter($filter);
    
    $dow=date('w',mktime(0,0,0,$month,$day,$year));
    $addclass='';
    if (($dow==0) or ($dow==6)) $addclass='weekend';

    $dailytotal["$day.$month.$year"]=$res->sum('spent');

    print "<td class='$addclass'>".sectostr($dailytotal["$day.$month.$year"],'HM')."</td>";
}
print "</tr>";

foreach ($monthtasks->keys('externalid') as $projectno) {

    $filter=new TupelFilter();
    $filter['externalid']=$projectno;

    $tasks=$monthtasks->filter($filter);

    print '<tr class="projectrow"><td><a href="#" onclick="collapse(1)">'.$projectno.' ('.count($tasks).') '."</a></td><td>".join(',',prefix_lookup($projectno,$prjnos))."</td><td>".'ALL'."</td><td>".sectostr($tasks->sum('spent'),$dmode,$dailytotal["$month.$year"])."</td>\n";

    /*    print_r($tasks); */
    for($day=1;$day<=$mdays;$day++) {
        $filter=new TupelFilter();
        $filter['time']=new Interval(mktime(0,0,0,$month,$day,$year),mktime(23,59,59,$month,$day,$year));

        $res=$tasks->filter($filter);

        $dow=date('w',mktime(0,0,0,$month,$day,$year));
        $addclass='';
        if (($dow==0) or ($dow==6)) $addclass='weekend';
        print "<td class='$addclass'>".sectostr($res->sum('spent'),$dmode,$dailytotal["$day.$month.$year"])."</td>";
    }

    foreach ($tasks->keys('id') as $taskid) {

#        $taskname=$tdl->field('TITLE',$taskid,'ID');

 
        $filter=new TupelFilter();
        $filter['id']=$taskid;
        $res=$tasks->filter($filter);
        $taskname=$res[0]['title'];
#        $task=$res[0]['l2task'] ? $res[0]['l2task'] : null;
        print "</tr><tr id='taskrow$taskid'><td>&nbsp;</td><td onmouseover='viewinfo($taskid)'>$taskname</td><td>ALL</td><td>".sectostr($res->sum('spent'),$dmode,$dailytotal["$month.$year"]).'</td>';

        for($day=1;$day<=$mdays;$day++) {
            $filter['time']=new Interval(mktime(0,0,0,$month,$day,$year),mktime(23,59,59,$month,$day,$year));
            $res=$tasks->filter($filter);
            $dow=date('w',mktime(0,0,0,$month,$day,$year));
            $addclass='';
            if (($dow==0) or ($dow==6)) $addclass='weekend';
            print "<td class='$addclass'>".sectostr($res->sum('spent'),$dmode,$dailytotal["$day.$month.$year"])."</td>";
        }

    }

    print "</tr>\n";
}
?>
</tbody>
</table>
<div id="tasks">
<table border="1">
<?php

$keys=$monthtasks->keys('id');
sort($keys);
foreach ($keys as $taskid) {
    print "<tr id='taskhead$taskid' class='task'><th>$taskid</th><th>";
    if ($task=$tdl->find($taskid)) {
        print $task->TITLE."</br>(".join(' -&gt; ',$task->parentchain('TITLE')).")</th></tr>";
        print "<tr id='task1body$taskid'><td></td><td>\n";
        print "<pre>".$task->COMMENTS."</pre>\n";
        print "</td></tr>";
#           foreach ($task as $subtask) {
#               print '  '.$subtask->attributes['ID'].' '.$subtask->attributes['TITLE']."\n";
#           }
    } else {
        print "---</th></tr>";
    }
    print "</div>";
}
?>
</table>
</div>
</body>
</html>
