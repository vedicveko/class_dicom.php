#!/usr/bin/php
<?PHP
#
# Compresses a DICOM file 
#

require_once('../class_dicom.php');

$file = (isset($argv[1]) ? $argv[1] : '');

if(!$file) {
  print "USAGE: ./compress.php <FILE>\n";
  exit;
}

if(!file_exists($file)) {
  print "$file: does not exist\n";
  exit;
}

$d = new dicom_tag;
$d->file = $file;
$d->load_tags();
$ts = $d->get_tag('0002', '0010');
$fsize = filesize($file);
print "Original: $ts ($fsize)\n";

$c = new dicom_convert;
$c->file = $file;
$c->compress('compressed.dcm');


$d->file = 'compressed.dcm';
$d->load_tags();
$ts = $d->get_tag('0002', '0010');
$fsize = filesize($d->file);
print "Compressed: $ts ($fsize)\n";

?>
