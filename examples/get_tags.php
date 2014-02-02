#!/usr/bin/php
<?PHP
#
# Prints out the DICOM tags in a file specified on the command line
#

require_once('../class_dicom.php');

$file = (isset($argv[1]) ? $argv[1] : '');

if(!$file) {
  print "USAGE: ./get_tags.php <FILE>\n";
  exit;
}

if(!file_exists($file)) {
  print "$file: does not exist\n";
  exit;
}

$d = new dicom_tag($file);
$d->load_tags();

print_r($d->tags);

$name = $d->get_tag('0010', '0010');
print "Name: $name\n";

?>
