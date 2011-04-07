#!/usr/bin/php
<?PHP
#
# Write DICOM tags to dean.dcm. $new_tags is an array of tags to be written
#
require_once('../class_dicom.php');

$d = new dicom_tag;
$d->file = 'dean.dcm';

$new_tags = array(
  '0010,0010' => 'VAUGHAN^DEAN',
  '0008,0080' => 'DEANLAND, AR'
);


$result = $d->write_tags($new_tags);

if($result) {
  print "$result\n";
}
else {
  system("./get_tags.php " . $d->file);
}

?>
