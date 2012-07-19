#!/usr/bin/php
<?PHP
// See: http://deanvaughan.org/wordpress/2012/07/class_dicom-php-example-send-all-dicom-files-in-a-directory/
require_once('class_dicom.php');

# WHERE YOUR DICOM FILES ARE
$temp_dir = '../temp';

# WHERE YOU ARE SENDING THEM TO
$target_host = 'kif.sxrmedical.com';
$target_port = '105';
$target_ae = 'BK';
$my_ae = 'BK';

if(!file_exists('bk')) {
  mkdir('bk');
}

$d = new dicom_net;

if($handle = opendir($temp_dir)) {
  while(false !== ($file = readdir($handle))) {
    if($file != "." && $file != "..") {

      print "Sending $file...\n";

      $d->file = "$temp_dir/$file";
      $ret = $d->send_dcm($target_host, $target_port, $my_ae, $target_ae);
      if($ret) {
        print "Send Error: $ret\n";
        continue;
      }
      else {
        print "Good Send\n";
        print "Moving $temp_dir/$file\n";
        rename("$temp_dir/$file", "bk/$file");
      }

    }
  }
  closedir($handle);
}

?>
