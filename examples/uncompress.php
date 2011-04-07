#!/usr/bin/php
<?PHP
#
# Uncompress a DICOM file 
#

require_once('../class_dicom.php');

$file = (isset($argv[1]) ? $argv[1] : '');

if(!$file) {
  print "USAGE: ./uncompress.php <FILE>\n";
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
$fsize = filesize($d->file);
print "Original: $ts ($fsize)\n";

$c = new dicom_convert;
$c->file = $file;
$c->uncompress('uncompressed.dcm');


$d->file = 'uncompressed.dcm';
$d->load_tags();
$ts = $d->get_tag('0002', '0010');
$fsize = filesize($d->file);
print "Uncompressed: $ts ($fsize)\n";

?>
