#!/usr/bin/php
<?PHP
#
# Convert a JPEG to DICOM format. $tags is a list of DICOM tags to use. 
# The template is the output of dcm2xml. Put the tag you want to replace inside of () in the template file.
#
require_once('../class_dicom.php');

$d = new dicom_convert;
$d->jpg_file = 'test.jpg';
$d->template = 'jpg_to_dcm.xml';
$d->temp_dir = 'dcm_temp';

$tags = array(
  '0008,0012' =>  date('Ymd'),
  '0008,0013' =>  date('Gis'), 
  '0008,0050' => 'ACCESSION123', 
  '0008,0080' => 'General Hospital', 
  '0008,0090' => 'Dr. Dean',
  '0008,1030' => 'Study Description',
  '0008,103e' => 'Series Description',
  '0010,0010' => 'VAUGHAN^DEAN',
  '0010,0020' => 'ID12345',
  '0010,0030' => '19700303',
  '0010,0040' => 'M',
  '0010,21b0' => 'Patient History',
  '0010,4000' => 'Patient Comments',
  '0018,0015' => 'Head',
  '0020,000d' => '1.3.51.0.7.2822962297.26312.19209.44846.7354.10266.42',
  '0020,000e' => '1.3.51.5156.4083.' . date('Ymd') .'.42',
  '0020,0011' => '1',
  '0020,0012' => '1',
  '0020,0013' => '1',
);

$dcm = $d->jpg_to_dcm($tags);

print "New file is $dcm\n";


?>
