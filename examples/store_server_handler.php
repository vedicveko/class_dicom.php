#!/usr/bin/php
<?PHP
#
# Processes files received by store_server.php 
#

function logger($message) {
  $now_time = date("Ymd G:i:s");

  $message = "$now_time - $message";

  $fh = fopen("dcm_temp/store_server.log", 'a') or die("can't open file");
  fwrite($fh, "$message\n");
  fclose($fh);

  print "$message\n";

}


require_once('../class_dicom.php');

$dir = (isset($argv[1]) ? $argv[1] : '');
$file = (isset($argv[2]) ? $argv[2] : '');
$sent_from_ae = (isset($argv[3]) ? $argv[3] : '');
$sent_to_ae = (isset($argv[4]) ? $argv[4] : '');

if(!$file || !$dir) {
  print "USAGE: SHOULD BE CALLED BY store_server.php\n";
  exit;
}

$d = new dicom_tag;
$d->file = "$dir/$file";

if(!file_exists($d->file)) {
  logger($d->file . ": does not exist");
  exit;
}


$d->load_tags();
$name = $d->get_tag('0010', '0010');
logger("Received $name from $sent_to_ae -> $sent_from_ae");

?>
