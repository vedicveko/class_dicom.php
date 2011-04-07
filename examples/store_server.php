#!/usr/bin/php
<?PHP
#
# Starts a DICOM store service on port 104. Any images received are stored in ./dcm_temp and then ./store_server_handler.php is ran.
#
require_once('../class_dicom.php');

print "Starting server on localhost:104\n";

$d = new dicom_net;
$d->store_server(104, './dcm_temp', './store_server_handler.php', 'store_server_config.cfg', 1);

?>
