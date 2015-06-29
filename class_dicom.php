<?PHP
/*
Dean Vaughan 2013 <dean@deanvaughan.org>
http://www.deanvaughan.org/projects/class_dicom_php/
*/

define('TOOLKIT_DIR', '/usr/local/bin'); // CHANGE THIS IF YOU HAVE DCMTK INSTALLED SOMEWHERE ELSE

// WINDOWS EXAMPLE
// define('TOOLKIT_DIR', 'C:/dcmtk/bin');

////////////////
///////////////
///////////////
// Are we running under Windows?
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
  define('RUNNING_WINDOWS', 1);
} 
else {
  define('RUNNING_WINDOWS', 0);
}

// If we're running under windows change where we look for our binaries.
// Just add .exe to the end of them.
if(RUNNING_WINDOWS) {
  define('BIN_DCMDUMP', TOOLKIT_DIR . '/dcmdump.exe');
  define('BIN_STORESCU', TOOLKIT_DIR . '/storescu.exe');
  define('BIN_STORESCP', TOOLKIT_DIR . '/storescp.exe');
  define('BIN_ECHOSCU', TOOLKIT_DIR . '/echoscu.exe');
  define('BIN_DCMJ2PNM', TOOLKIT_DIR . '/dcmj2pnm.exe');
  define('BIN_DCMODIFY', TOOLKIT_DIR . '/dcmodify.exe');
  define('BIN_DCMCJPEG', TOOLKIT_DIR . '/dcmdjpeg.exe');
  define('BIN_DCMDJPEG', TOOLKIT_DIR . '/dcmcjpeg.exe');
  define('BIN_XML2DCM', TOOLKIT_DIR . '/xml2dcm.exe');
  define('BIN_IMG2DCM', TOOLKIT_DIR . '/img2dcm.exe');
}
else {
  define('BIN_DCMDUMP', TOOLKIT_DIR . '/dcmdump');
  define('BIN_STORESCU', TOOLKIT_DIR . '/storescu');
  define('BIN_STORESCP', TOOLKIT_DIR . '/storescp');
  define('BIN_ECHOSCU', TOOLKIT_DIR . '/echoscu');
  define('BIN_DCMJ2PNM', TOOLKIT_DIR . '/dcmj2pnm');
  define('BIN_DCMODIFY', TOOLKIT_DIR . '/dcmodify');
  define('BIN_DCMCJPEG', TOOLKIT_DIR . '/dcmdjpeg');
  define('BIN_DCMDJPEG', TOOLKIT_DIR . '/dcmcjpeg');
  define('BIN_XML2DCM', TOOLKIT_DIR . '/xml2dcm');
  define('BIN_IMG2DCM', TOOLKIT_DIR . '/img2dcm');
}

/*
$d = new dicom_tag;
$d->file = 'SOME_IMAGE.dcm';
$d->load_tags();
$name = $d->get_tag('0010', '0010');
*/

class dicom_tag {

  var $tags = array();
  var $file = -1;

  function __construct($file = '') {
    $this->file = $file;
    if(file_exists($this->file)) {
      if(is_dcm($this->file)) {
        $this->load_tags();
      }
    }
  }

### LOAD DICOM TAGS FROM A FILE INTO AN ARRAY ($this->tags). $this->file is the filename of the image.
  function load_tags() {
    $file = $this->file;
    $dump_cmd = BIN_DCMDUMP . " -M +L +Qn $file";
    $dump = Execute($dump_cmd);

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

    $write_cmd = BIN_DCMODIFY . " $str " .
               "-nb \"" . $this->file . "\"";
    $out = Execute($write_cmd);
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

  function __construct($file = '') {
    $this->file = $file;
  }
  
### Convert a DICOM image to JPEG. $this->file is the filename of the image.
### $this->jpg_quality is an optional value (0-100) that'll set the quality of the JPEG produced
  function dcm_to_jpg() {

    $filesize = 0;

    $this->jpg_file = $this->file . '.jpg';
   
    $convert_cmd = BIN_DCMJ2PNM . " +oj +Jq " . $this->jpg_quality . " --use-window 1 \"" . $this->file . "\" \"" . $this->jpg_file . "\"";
    $out = Execute($convert_cmd);

    if(file_exists($this->jpg_file)) {
      $filesize = filesize($this->jpg_file);
    }

    if($filesize < 10) {
      $convert_cmd = BIN_DCMJ2PNM . " +Wm +oj +Jq " . $this->jpg_quality . " \"" . $this->file . "\" \"" . $this->jpg_file . "\"";
      $out = Execute($convert_cmd);
    }

    return($this->jpg_file);

  }

### Convert $this->file into a JPEG thumbnail.
### Optional $this->tn_size will let you change the width of the thumbnail produced
  function dcm_to_tn() {
    $filesize = 0;
    $this->tn_file = $this->file . '_tn.jpg';

    $convert_cmd = BIN_DCMJ2PNM . " +oj +Jq 75 +Sxv " . $this->tn_size . " --use-window 1 \"" . $this->file . "\" \"" . $this->tn_file . "\"";
    $out = Execute($convert_cmd);

    if(file_exists($this->tn_file)) {
      $filesize = filesize($this->tn_file);
    }

    if($filesize < 10) {
      $convert_cmd = BIN_DCMJ2PNM . " +Wm +oj +Jq 75 +Sxv  " . $this->tn_size . " \"" . $this->file . "\" \"" . $this->tn_file . "\"";
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

    $uncompress_cmd = BIN_DCMDJPEG . " \"" . $this->file . "\" \"" . $new_file . "\"";
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

    $uncompress_cmd = BIN_DCMCJPEG . " \"" . $this->file . "\" \"" . $new_file . "\"";
    $out = Execute($compress_cmd);
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
    $xml2dcm_cmd = BIN_XML2DCM . " $temp_xml " . $this->file;
    $out = Execute($xml2dcm_cmd);
    if($out) {
      return($out);
    }
    unlink($temp_xml); // NO LONGER NEEDED

    // Add the JPEG image to the DCM we just made
    $combine_cmd = BIN_IMG2DCM . " -df " . $this->file . " -stf " . $this->file . " -sef " . $this->file . " \"" . $this->jpg_file . "\" " . $this->file;
    $out = Execute($combine_cmd);
    if($out) {
      return($out);
    }

    return($this->file);
  }

### You will want to change $vid_cmd to be applicable to your system
  function multiframe_to_video($format = 'mp4', $framerate = 24, $temp_dir = "./video_temp") {

    $want = 7;

    $vid_file = basename($this->file) . ".$format";

    if(dirname($this->file) == '.') {
      $this->file = dirname(__FILE__) . '/' . $this->file;
    }

    if (!file_exists($temp_dir)) {
      mkdir($temp_dir, 0777);
    }

    # Split each frame into a jpeg
    $curr_dir = getcwd();
    chdir($temp_dir);
    $split_cmd = BIN_DCMJ2PNM . " +Fa +oj +Jq 100 \"" . $this->file . "\" frame";
    $out = Execute($split_cmd);

    if($out) {
      return("$split_cmd: $out");
    }

    ## rename our jpegs into something suited for ffmpeg (001, 002, 003, ect)
    $x = 0;

    if ($handle = opendir('.')) {
      while (false !== ($file = readdir($handle))) {
        if ($file == '.' || $file == '..') {
          continue;
        }
        if (!strstr($file, '.jpg')) {
          continue;
        }

        $new_name = str_replace('frame.', '', $file);
        $l = strlen($new_name);
        $diff = $want - $l;
        while ($diff) {
          $new_name = "0$new_name";
          $diff--;
        }
        if ($file != $new_name) {
          rename($file, $new_name);
        }
        $x++;
      }
      closedir($handle);
    }

    if ($x < 10) {
      $framerate = 10;
    }

    if(file_exists($vid_file)) {
      unlink($vid_file);
    }

    $vid_cmd = "ffmpeg -r $framerate -b 5000k -i %03d.jpg -vcodec libx264 \"$vid_file\"";
    $out = Execute($vid_cmd);

    /* This is a special case, probably only useful to me.
        if(strstr($out, 'height not divisible by 2')) {
          print "Running height fix\n";
          unlink($vid_file);
          $out = Execute("mogrify -resize 992x504 *.jpg");
          $vid_cmd = "/usr/local/bin/ffmpeg -r $framerate -b 5000k -i %03d.jpg -vcodec libx264 $vid_file";
          $out = Execute($vid_cmd);
        }
    */

    chdir($curr_dir);

    return ("$temp_dir/$vid_file");
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

    system(BIN_STORESCP . " $dflag -dhl -td 20 -ta 20 --fork -xf $config_file Default -od $dcm_dir -xcr \"$handler_script \"#p\" \"#f\" \"#c\" \"#a\"\" $port");
  }

### Performs an echoscu (DICOM ping) on $host $port
  function echoscu($host, $port, $my_ae = 'DEANO', $remote_ae = 'DEANO') {
    $ping_cmd = BIN_ECHOSCU . " -ta 5 -td 5 -to 5 -aet \"$my_ae\" -aec \"$remote_ae\" $host $port";
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
      $send_command = BIN_STORESCU . " -ta 10 -td 10 -to 10 $ts_flag -aet \"$my_ae\" -aec $remote_ae $host $port +sd \"$to_send\"";
    }
    else {
      $send_command = BIN_STORESCU . " -ta 10 -td 10 -to 10 $ts_flag -aet \"$my_ae\" -aec $remote_ae $host $port \"$to_send\"";
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

### I'm keeping this outside of the class so it is easier to get to. This may change in the future.
function is_dcm($file) {
  $dump_cmd = BIN_DCMDUMP . " -M +L +Qn $file";
  $dump = Execute($dump_cmd);

  if(strstr($dump, 'error')) {
    return(0);
  }
  else if(strstr($dump, 'Modality')) {
    return(1);
  }

  return(0);
}



?>
