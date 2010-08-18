<?php
/*
	admin/db_backup-process.php
	
	Access: admin only

	Performs export of the MySQL database, by executing mysqldump, creating
	temp files and then outputting the file for download.
*/


// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get("admin"))
{
	/*
		Create temp files for download
	*/
	$file_config	= file_generate_tmpfile();
	$file_export	= file_generate_tmpfile();


	/*
		Write authentication information into temp config file 
		this allows us to prevent the exposure of the DB password on the CLI
	*/

	$fh = fopen($file_config, "w");
	fwrite($fh, "[mysqldump]\n");
	fwrite($fh, "host=". $config["db_host"] ."\n");
	fwrite($fh, "user=". $config["db_user"] ."\n");
	fwrite($fh, "password=". $config["db_pass"] ."\n");
	fclose($fh);


	/*
		Export Database
	*/

	$dbname		= sql_get_singlevalue("SELECT DATABASE() as value");
	$app_mysqldump	= sql_get_singlevalue("SELECT value FROM config WHERE name='APP_MYSQL_DUMP'");

	system("$app_mysqldump --defaults-file=$file_config $dbname | gzip > $file_export");



	/*
		Set HTTP headers
	*/

	$filename = "amberdms_bs_export_". time() .".sql.gz";
	
	// required for IE, otherwise Content-disposition is ignored
	if (ini_get('zlib.output_compression'))
		ini_set('zlib.output_compression', 'Off');

	header("Pragma: public"); // required
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private",false); // required for certain browsers 
	header("Content-Type: application/force-download");
	
	header("Content-Disposition: attachment; filename=\"".basename($filename)."\";" );
	header("Content-Transfer-Encoding: binary");

	// tell the browser how big the file is (in bytes)
	header("Content-Length: ". filesize($file_export) ."");



	/*
		Print out the file contents for browser download
	*/
	readfile($file_export);


	/*
		Cleanup
	*/
	unlink($file_config);
	unlink($file_export);
	
} // end of "is user logged in?"
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
