<?PHP
#
# You can call this from a URL and it'll print out the header for dean.dcm
#
require_once('../class_dicom.php');

$file = 'dean.dcm';

if(!file_exists($file)) {
  print "$file: does not exist\n";
  exit;
}

$d = new dicom_tag;
$d->file = $file;
$d->load_tags();

print "<pre>";

print_r($d->tags);

$name = $d->get_tag('0010', '0010');
print "Name: $name\n";

?>
