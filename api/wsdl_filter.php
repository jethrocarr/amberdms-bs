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

$uri = 'https://'.$_SERVER['SERVER_NAME'];
$uri .= ($_SERVER['SERVER_PORT'] == 443) ? '' : ':'.$_SERVER['SERVER_PORT'];
$uri .= '/'.ltrim(dirname($_SERVER['PHP_SELF']), '/').'/';

foreach($result as $address) {
    $address['location'] = preg_replace('/^(.*?)\/api(\/)/', $uri, $address['location']);
}

// format
$dom = dom_import_simplexml($xml)->ownerDocument;
$dom->validateOnParse = false;
$dom->resolveExternals = false;
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->encoding = 'UTF-8';

// output
header('Content-type: application/wsdl+xml');
echo $dom->saveXML();
