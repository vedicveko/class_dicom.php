<?PHP

define('TOOLKIT_DIR', '/usr/local/bin'); // CHANGE THIS IF YOU HAVE DCMTK INSTALLED SOMEWHERE ELSE

/*

Dean Vaughan 2011 <dean@deanvaughan.org>
http://www.deanvaughan.org/projects/class_dicom_php/

*/

/*
$d = new dicom_tag;
$d->file = 'SOME_IMAGE.dcm';
$d->load_tags();
$name = $d->get_tag('0010', '0010');
*/

class dicom_tag {

  var $tags = array();
  var $file = -1;

### LOAD DICOM TAGS FROM A FILE INTO AN ARRAY ($this->tags). $this->file is the filename of the image.
  function load_tags() {
    $file = $this->file;
    $dump_cmd = TOOLKIT_DIR . "/dcmdump -M +L +Qn $file";
    $dump = `$dump_cmd`;

    if(!$dump) {
      return(0);
    }

#print "$dump\n";
#exit;

    $this->tags = array();

    foreach(explode("\n", $dump) as $line) {

      $ge = '';

      $t = preg_match_all("/\((.*)\) [A-Z][A-Z]/", $line, $matches);
      if(isset($matches[1][0])) {
        $ge = $matches[1][0];
        if(!isset($this->tags["$ge"])) {
          $this->tags["$ge"] = '';
        }
      }

      if(!$ge) {
        continue;
      }

      $val = '';
      $found = 0;
      $t = preg_match_all("/\[(.*)\]/", $line, $matches);
      if(isset($matches[1][0])) {
        $found = 1;
        $val = $matches[1][0];

        if(is_array($this->tags["$ge"])) { // Already an array
          $this->tags["$ge"][] = $val;
        }
        else { // Create new array
          $old_val = $this->tags["$ge"];
          if($old_val) {
            $this->tags["$ge"] = array();
            $this->tags["$ge"][] = $old_val;
            $this->tags["$ge"][] = $val;
          }
          else {
            $this->tags["$ge"] = $val;
          }
        }
      }

      if(is_array($this->tags["$ge"])) {
        $found = 1;
      }

      if(!$found) { // a couple of tags are not in [] preceded by =
        $t = preg_match_all("/\=(.*)\#/", $line, $matches);
        if(isset($matches[1][0])) {
          $found = 1;
          $val = $matches[1][0];
          $this->tags["$ge"] = rtrim($val);
        }
      }

      if(!$found) { // a couple of tags are not in []
        $t = preg_match_all("/[A-Z][A-Z] (.*)\#/", $line, $matches);
        if(isset($matches[1][0])) {
          $found = 1;
          $val = $matches[1][0];
          if(strstr($val, '(no value available)')) {
            $val = '';
          }
          $this->tags["$ge"] = rtrim($val);
        }
      }
    }
  }

### AFTER load_tags() HAS BEEN CALLED, USE THIS TO GET A SPECIFIC TAG
  function get_tag($group, $element) {
    $val = '';
    if(isset($this->tags["$group,$element"])) {
      $val = $this->tags["$group,$element"];
    }
    return($val);
  }

### WRITE TAGS INTO AN IMAGE, $tag_arr SHOULD LOOK LIKE:
/*
$tag_arr = array(
  '0010,0010' => 'VAUGHAN^DEAN',
  '0008,0080' => 'DEANLAND, AR'
);
*/
### $this->file is the filename of the image.
  function write_tags($tag_arr) {
    if(!is_array($tag_arr)) {
      return(1);
    }

    $str = '';
    foreach($tag_arr as $group => $element) {
      $str .= "-i \"($group)=$element\" ";
    }

    $write_cmd = TOOLKIT_DIR . "/dcmodify $str " .
               "-nb \"" . $this->file . "\"";
    $out = `$write_cmd`;
    if(!$out) {
      return(0);
    }
    else {
      return($out);
    }
  }


}

class dicom_convert {

  var $file = '';  
  var $jpg_file = '';
  var $tn_file = '';
  var $jpg_quality = 100;
  var $tn_size = 125;
  
### Convert a DICOM image to JPEG. $this->file is the filename of the image.
### $this->jpg_quality is an optional value (0-100) that'll set the quality of the JPEG produced
  function dcm_to_jpg() {

    $filesize = 0;

    $this->jpg_file = $this->file . '.jpg';
   
    $convert_cmd = TOOLKIT_DIR . "/dcmj2pnm +oj +Jq " . $this->jpg_quality . " --use-window 1 \"" . $this->file . "\" \"" . $this->jpg_file . "\"";
    $out = Execute($convert_cmd);

    if(file_exists($this->jpg_file)) {
      $filesize = filesize($this->jpg_file);
    }

    if($filesize < 10) {
      $convert_cmd = TOOLKIT_DIR . "/dcmj2pnm +Wm +oj +Jq " . $this->jpg_quality . " \"" . $this->file . "\" \"" . $this->jpg_file . "\"";
      $out = Execute($convert_cmd);
    }

    return($this->jpg_file);

  }

### Convert $this->file into a JPEG thumbnail.
### Optional $this->tn_size will let you change the width of the thumbnail produced
  function dcm_to_tn() {
    $filesize = 0;
    $this->tn_file = $this->file . '_tn.jpg';

    $convert_cmd = TOOLKIT_DIR . "/dcmj2pnm +oj +Jq 75 +Sxv " . $this->tn_size . " --use-window 1 \"" . $this->file . "\" \"" . $this->tn_file . "\"";
    $out = Execute($convert_cmd);

    if(file_exists($this->tn_file)) {
      $filesize = filesize($this->tn_file);
    }

    if($filesize < 10) {
      $convert_cmd = TOOLKIT_DIR . "/dcmj2pnm +Wm +oj +Jq 75 +Sxv  " . $this->tn_size . " \"" . $this->file . "\" \"" . $this->tn_file . "\"";
      $out = Execute($convert_cmd);
    }

    return($this->tn_file);
  }

### This will uncompress $this->file. 
### Optionally, you can give the output file a different name than the original by passing $new_file
  function uncompress($new_file = '') {
    if(!$new_file) {
      $new_file = $this->file;
    }

    $uncompress_cmd = TOOLKIT_DIR . "/dcmdjpeg \"" . $this->file . "\" \"" . $new_file . "\"";
    $out = Execute($uncompress_cmd);
    return($new_file);
  }

// THIS REALLY SHOULD BE EXPANDED TO INCLUDE OTHER COMPRESSION OPTIONS
### This will JPEG losslessly compress $this->file 
### Optionally, you can give the output file a different name than the original by passing $new_file
  function compress($new_file = '') {
    if(!$new_file) {
      $new_file = $this->file;
    }

    $uncompress_cmd = TOOLKIT_DIR . "/dcmcjpeg \"" . $this->file . "\" \"" . $new_file . "\"";
    $out = Execute($uncompress_cmd);
    return($new_file);
  }

### See examples/jpg_to_dcm.php for an example, it'll help things make sense. 
/*
$this->jpg_file is the JPEG you plan on turning into a DICOM file.

Make an array that maps the values to the tags you want your DICOM file to contain. Like this:
$arr_info = array(
  '0010,0010' => 'VAUGHAN^DEAN', // Patient Name 
  '0008,0080' => 'DEANLAND, AR'  // Institution
);
You'll actually need to map more fields out to get a usable DICOM file. examples/jpg_to_dcm.php has an example of what's
probably the minimum you need to keep most other DICOM software happy with the files you're producing.

Point $this->template towards examples/jpg_to_dcm.xml You'll notice it's a shortened output of dcm2xml. I've put the tag 
name in () where I want the value to go.

*/
  function jpg_to_dcm($arr_info) {

    // USING THE DATA IN OUR ARRAY AND THE TEMPLATE, BUILD AN XML FILE FOR DCMTK
    $xml = file_get_contents($this->template);
    $temp_xml = $this->temp_dir . '/' . date('YmdGis') . rand(0, 30) . '.xml';

    foreach($arr_info as $tag => $value) {
      $xml = str_replace("($tag)", $value, $xml);
    }

    file_put_contents($temp_xml, $xml);

    // Make a DCM file using the XML we just made as the header info.
    $this->file = $this->jpg_file . '.dcm';
    $xml2dcm_cmd = TOOLKIT_DIR . "/xml2dcm $temp_xml " . $this->file;
    $out = Execute($xml2dcm_cmd);
    if($out) {
      return($out);
    }
    unlink($temp_xml); // NO LONGER NEEDED

    // Add the JPEG image to the DCM we just made
    $combine_cmd = TOOLKIT_DIR . "/img2dcm -df " . $this->file . " -stf " . $this->file . " -sef " . $this->file . " \"" . $this->jpg_file . "\" " . $this->file;
    $out = Execute($combine_cmd);
    if($out) {
      return($out);
    }

    return($this->file);
  }

### SOME DAY...
  function pdf_to_dcm($arr_info) {

  }

  function pdf_to_dcmcr($arr_info) {

  }

}


class dicom_net {

  var $transfer_syntax = '';
  var $file = '';

### $port is the tcp port to listen on
### $dcm_dir is where the DICOM files go after being received. 
### $handler_script is a program that will be ran after each DICOM file is received. The DICOM image's filename, $dcm_dir, 
### and the AE titles will be passed via the command line
### $config_file is the storescp config file. man storescp will get you a run down. The file provided in 
### examples/store_server_config.cfg will work with all of the common ways of doing things
  function store_server($port, $dcm_dir, $handler_script, $config_file, $debug = 0) {
    $dflag = '';
    if($debug) {
      $dflag = '-v -d ';
    }

    system(TOOLKIT_DIR . "/storescp $dflag -dhl -td 20 -ta 20 --fork -xf $config_file Default -od $dcm_dir -xcr \"$handler_script \"#p\" \"#f\" \"#c\" \"#a\"\" $port");
  }

### Performs an echoscu (DICOM ping) on $host $port
  function echoscu($host, $port, $my_ae = 'DEANO', $remote_ae = 'DEANO') {
    $ping_cmd = TOOLKIT_DIR . "/echoscu -ta 5 -td 5 -to 5 -aet \"$my_ae\" -aec \"$remote_ae\" $host $port";
    $out = Execute($ping_cmd);
    if(!$out) {
      return(0);
    }
    return($out);
  }

### Sends $this_file to $host $port.
### If $send_batch is enabled it'll send all of the files in the same directory as $this->file in one association
  function send_dcm($host, $port, $my_ae = 'DEANO', $remote_ae = 'DEANO', $send_batch = 0) {

    if(!$this->transfer_syntax) {
      $tags = new dicom_tag;
      $tags->file = $this->file;
      $tags->load_tags();
      $this->transfer_syntax = $tags->get_tag('0002', '0010');
    }

    $ts_flag = '';
    switch($this->transfer_syntax) {
      case 'JPEGBaseline':
        $ts_flag = '-xy';
      break;
      case 'JPEGExtended:Process2+4':
        $ts_flag = '-xx';
      break;
      case 'JPEGLossless:Non-hierarchical-1stOrderPrediction':
        $ts_flag = '-xs';
      break;
    }

    $to_send = $this->file;

    if($send_batch) {
      $to_send = dirname($this->file);
      $send_command = TOOLKIT_DIR . "/storescu -ta 10 -td 10 -to 10 $ts_flag -aet \"$my_ae\" -aec $remote_ae $host $port +sd \"$to_send\"";
    }
    else {
      $send_command = TOOLKIT_DIR . "/storescu -ta 10 -td 10 -to 10 $ts_flag -aet \"$my_ae\" -aec $remote_ae $host $port \"$to_send\"";
    }

    $out = Execute($send_command); 
    if($out) {
      return($out);
    }
    return(0);

  }

}

### CAPTURES ALL OF THE GOOD OUTPUTS!
function Execute($command) {

  $command .= ' 2>&1';
  $handle = popen($command, 'r');
  $log = '';

  while (!feof($handle)) {
    $line = fread($handle, 1024);
    $log .= $line;
  }
  pclose($handle);

  return $log;
}

?>
