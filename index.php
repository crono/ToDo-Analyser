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

<?php


// Unicode BOM is U+FEFF, but after encoded, it will look like this.
define ('UTF32_BIG_ENDIAN_BOM'   , chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF));
define ('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00));
define ('UTF16_BIG_ENDIAN_BOM'   , chr(0xFE) . chr(0xFF));
define ('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE));
define ('UTF8_BOM'               , chr(0xEF) . chr(0xBB) . chr(0xBF));

#$xml_parser=xml_parser_create('UTF-8');

#xml_parse_into_struct($xml_parser, $text, $vals, $index);
#xml_parser_free($xml_parser);
#
#
class Task implements arrayaccess {

        private $subtasks = array();
        private $attributes = array();
        private $parent;

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
                $this->subtasks[] = $value;
            } else {
                $this->subtasks[$offset] = $value;
            }
        }

        public function offsetExists($offset) {
            return isset($this->subtasks[$offset]);
        }

        public function offsetUnset($offset) {
            unset($this->subtasks[$offset]);
        }

        public function offsetGet($offset) {
            return isset($this->subtasks[$offset]) ? $this->subtasks[$offset] : null;
        }

    public function asxml() {
        $xmlrep='<TASK ';
        foreach ($this->attributes as $key => $value ) {
            if ($key=='COMMENTS') $value=str_replace('\n',"\n",$value);
#            if ($key=='COMMENTS') $value='blub';
            $xmlrep.=$key.'="'.htmlspecialchars($value).'" ';
        }
        $xmlrep.=">\n";
        foreach ($this->subtasks as $task) {
            $xmlrep.=$task->asxml();
        }
        $xmlrep.="</TASK>\n";
        return $xmlrep;
    }

}

class ToDoList implements arrayaccess {
    private $parser = NULL;
    private $attributes = NULL;

    private $tasklist=array();
    private $taskpointer;
/*
  ["PROJECTNAME"]=>
  string(19) "Walter's ToDo Liste"
  ["FILEFORMAT"]=>
  string(1) "9"
  ["EARLIESTDUEDATE"]=>
  string(10) "0.00000000"
  ["NEXTUNIQUEID"]=>
  string(3) "428"
  ["FILENAME"]=>
  string(9) "tasks.tdl"
  ["LASTMODIFIED"]=>
  string(10) "2012-02-09"
  ["FILEVERSION"]=>
  string(4) "6455"
  ["CHECKEDOUTTO"]=>
  string(13) "T80258:tdo366"
 */  

    function __construct () {
        $this->parser = xml_parser_create();
        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, "tag_open", "tag_close");
        xml_set_character_data_handler($this->parser, "cdata");
    }

    function asxml() {
        $xmlrep='<TODOLIST ';
        foreach ($this->attributes as $key => $value ) {
#            if ($key=='FILENAME') $value=basename($filename);
            if ($key=='CHECKEDOUTTO') $value='';
            if ($key=='FILEVERSION') $value++;
            $xmlrep.= $key.'="'.$value.'" ';
        }
        $xmlrep.=">\n";

        foreach ($this->tasklist as $task) {
            $xmlrep.= $task->asxml();
        }

        $xmlrep.="</TODOLIST>";

        return $xmlrep;
    }

    function replace_comments ($matches) {
                $comment=$matches[1];
                $comment=str_replace(array("\r\n", "\n", "\r"),'\n',$comment);
                return 'COMMENTS="'.$comment.'"';
    }

    public function readfile($filename) {
        # Load File and change encoding
        $data=file_get_contents($filename);
        $data=mb_convert_encoding($data,'UTF-8','UTF-16LE');
        # replace multiline comments to single line with \n characters
        $data=preg_replace_callback('/COMMENTS\="(.*?)"/s',array($this,'replace_comments'),$data);
        
        # Start building the task-tree with the root-node
        $this->taskpointer=$this;
        # Start the parser
        xml_parse($this->parser, $data);
    }

    public function writefile($filename) {
#        print_r($this->attributes);
#        var_dump($this->tasklist);
        $fcont='<?xml version="1.0" encoding="windows-1252" ?>'."\n";
        $fcont.=$this->asxml();
        $file=fopen($filename,'w');
        fwrite($file,chr(0xFF) . chr(0xFE).mb_convert_encoding($fcont,'UTF-16LE','UTF-8'));
#        fwrite($file,mb_convert_encoding(,'UTF-8','UTF-16LE');
        fclose($file);
    }

    public function assign($attributes) {
       $this->attributes=$attributes;
    }

    /** XML-Functions **/

    function tag_open($parser, $tag, $attributes) {
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
            default:
                print "OPEN UNKNOWN $tag!!";
                if (isset($attributes)) var_dump($attributes); 
        }

    }

    function getparent() {
            return $this;
    }

    function cdata($parser, $cdata) 
    {
      if(!trim($cdata)) return;
      print "CDATA: ";
      var_dump($parser, $cdata); 
    }

    function tag_close($parser, $tag) 
    {
        switch ($tag) {
            case 'TODOLIST':
                break;
            case 'TASK':
                $this->taskpointer=$this->taskpointer->getparent();
                break;
            default:
                print "CLOSE UNKNOWN $tag!!";
                if (isset($attributes)) var_dump($attributes); 
        }
    }


        /** ARRAY ACCESS FUNCTIONS **/

        public function offsetSet($offset, $value) {
            if (is_null($offset)) {
                $this->tasklist[] = $value;
            } else {
                $this->tasklist[$offset] = $value;
            }
        }

        public function offsetExists($offset) {
            return isset($this->tasklist[$offset]);
        }

        public function offsetUnset($offset) {
            unset($this->tasklist[$offset]);
        }

        public function offsetGet($offset) {
            return isset($this->tasklist[$offset]) ? $this->tasklist[$offset] : null;
        }



} // end of class xml



$tdl = new ToDoList();
$tdl->readfile('test/tasks.tdl');
$tdl->writefile('test/new.tdl');

echo "</PRE>";


?>
<?php

# print "Encoding: ".detect_utf_encoding('tasks.tdl')."\n";
#print "Encoding: ".mb_detect_encoding($text,'auto')."\n";

$data=file_get_contents('test/tasks.tdl');
$data=mb_convert_encoding($data,'UTF-8','UTF-16LE');
print $data;

$data=file_get_contents('test/new.tdl');
$data=mb_convert_encoding($data,'UTF-8','UTF-16LE');
print $data;

?>
</body>
</html>
