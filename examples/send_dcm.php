#!/usr/bin/php
<?PHP
#
# Sends a DICOM file to localhost 104... what the store_server.php example defaults too 
#

require_once('../class_dicom.php');

$file = (isset($argv[1]) ? $argv[1] : '');

if(!$file) {
  print "USAGE: ./send_dcm.php <FILE>\n";
  exit;
}

if(!file_exists($file)) {
  print "$file: does not exist\n";
  exit;
}

$d = new dicom_net;
$d->file = $file;

print "Sending file...\n";

$out = $d->send_dcm('localhost', '104', 'DEANO', 'example');

if($out) {
  print "$out\n\nSomething bad happened!\n";
  exit;
}

print "Sent!\n";

?>
