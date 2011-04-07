<?PHP

define('TOOLKIT_DIR', '/usr/local/dicom/bin');

/*
$d = new dicom_tag;
$d->file = 'SOME_IMAGE.dcm';
$d->load_tags();
$name = $d->get_tag('0010', '0010');
*/

class dicom_tag {

  var $tags = array();
  var $file = -1;


  function load_tags() {
    $file = $this->file;
    $dump_cmd = TOOLKIT_DIR . "/dcmdump -M +L +Qn $file";
    $dump = `$dump_cmd`;

    if(!$dump) {
      return(0);
    }

    foreach(explode("\n", $dump) as $line) {

      $t = preg_match_all("/\((.*)\) [A-Z][A-Z]/", $line, $matches);
      if(isset($matches[1][0])) {
        $ge = $matches[1][0];
        $this->tags["$ge"] = '';
      }

      $val = '';
      $t = preg_match_all("/\[(.*)\]/", $line, $matches);
      if(isset($matches[1][0])) {
        $val = $matches[1][0];
        $this->tags["$ge"] = $val;
      }
      else { // a couple of tags are not in []
        $t = preg_match_all("/\=(.*)\#/", $line, $matches);
        if(isset($matches[1][0])) {
          $val = $matches[1][0];
          $this->tags["$ge"] = rtrim($val, '');
        }
      }
    }
  }

  function get_tag($group, $element) {
    $val = '';
    if(isset($this->tags["$group,$element"])) {
      $val = $this->tags["$group,$element"];
    }
    return($val);
  }

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
  var $transfer_syntax = '';
  var $jpg_file = '';
  var $tiff_file = '';
  
  // REQUIRES IMAGE MAGICK
  function dcm_to_jpg() {

    $filesize = 0;
    $jpg_quality = 95;

    $this->jpg_file = $this->file . '.jpg';
    $this->tiff_file = $this->file . '.tiff';
   
    if(!$this->transfer_syntax) {
      $tags = new dicom_tag;
      $tags->file = $this->file;
      $tags->load_tags();
      $this->transfer_syntax = $tags->get_tag('0002', '0010');
    }

    if(strstr($this->transfer_syntax, 'LittleEndian')) {
      $convert_cmd = TOOLKIT_DIR . "/dcm2pnm +Tn --write-tiff --use-window 1 \"" . $this->file . "\" \"" . $this->tiff_file . "\"";
      $out = `$convert_cmd`;

      if(file_exists($this->tiff_file)) {
        $filesize = filesize($this->tiff_file);
      }

      if($filesize < 10) {
        $convert_cmd = TOOLKIT_DIR . "/dcm2pnm +Wm +Tn --write-tiff \"" . $this->file . "\" \"" . $this->tiff_file . "\"";
        $out = `$convert_cmd`;
      }

      $convert_cmd = "convert -quality $jpg_quality \"" . $this->tiff_file . "\" \"" . $this->jpg_file . "\"";
      $out = `$convert_cmd`;
      if(file_exists($this->tiff_file)) {
        unlink($this->tiff_file);
      }
    }
    else if(strstr($this->transfer_syntax, 'JPEG')) {

      if(strstr($this->transfer_syntax, 'Baseline') || strstr($this->transfer_syntax, 'Lossless')) {
        $jpg_quality = 100;
      }

      $convert_cmd = TOOLKIT_DIR . "/dcmj2pnm +oj +Jq $jpg_quality --use-window 1 \"" . $this->file . "\" \"" . $this->jpg_file . "\"";
      $out = `$convert_cmd`;

      if(file_exists($this->jpg_file)) {
        $filesize = filesize($this->jpg_file);
      }

      if($filesize < 10) {
        $convert_cmd = TOOLKIT_DIR . "/dcmj2pnm +Wm +oj +Jq $jpg_quality \"" . $this->file . "\" \"" . $this->jpg_file . "\"";
        $out = `$convert_cmd`;
      }
    }

    return($this->jpg_file);

  }

  // REQUIRES IMAGE MAGICK
  function dcm_to_tn() {
    $this->dcm_to_jpg();
    $this->tn_file = $this->jpg_file;
    $this->tn_file = preg_replace('/.jpg$/', '_tn.jpg', $this->tn_file);

    $convert_cmd = "convert -resize 125 -quality 75 \"" . $this->jpg_file . "\" \"" . $this->tn_file . "\"";
    $out = `$convert_cmd`;
    return($this->tn_file);
  }

  function jpg_to_dcm() {

  }

  function uncompress() {

  }

  function compress() {

  }

}


class dicom_net {

  function store_server($port, $dcm_dir, $handler_script, $config_file, $debug = 0) {
    $dflag = '';
    if($debug) {
      $dflag = '-d -v ';
    }

    system(TOOLKIT_DIR . "/storescp $dflag -td 20 -ta 20 --fork -xf $config_file Default -od $dcm_dir -xcr \"$handler_script \"#p\" \"#f\" \"#c\" \"#a\"\" $port");
  }

  function echoscu($host, $port, $my_ae = 'DEANO', $remote_ae = 'DEANO') {
    $ping_cmd = TOOLKIT_DIR . "/echoscu -ta 5 -td 5 -to 5 -aet \"$my_ae\" -aec \"$remote_ae\" $host $port";
    $out = `$ping_cmd`;
    if(!$out) {
      return(0);
    }
    return($out);
  }

  function send_dcm() {

  }


}



?>
