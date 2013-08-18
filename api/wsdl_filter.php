<?php
/*
	api/wsdl_filter.php

	Filters WSDL to include relative paths to actions and beautiful XML output
*/

// make sure we have a file
if(!isset($_GET['wsdl'])) {
    die('WSDL_FILTER_ERROR');
}

// load xml file
$wsdl_file = __DIR__.'/'.ltrim($_GET['wsdl'], '/');
$xml = simplexml_load_file($wsdl_file);

// rewrite soap address locations
$result = $xml->xpath('//soap:address[@location]');

foreach($result as $address) {
    $address['location'] = preg_replace('/^(.*?)\/api(\/)/', 'https://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF']).'/', $address['location']);
}

// output
header('Content-type: application/wsdl+xml');
echo $xml->asXML();
