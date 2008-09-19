<?php
/*
	inc_file_uploads.php

	Provides functions for displaying or uploading files. This is used for attaching files
	to the journal, as well as for other functions.


	FILE STORAGE

	AMBERPHPLIB allows for two different storage methods of uploaded files:

	1. Upload all files to a directory on the webserver

		Advantages:
			* Keeps the SQL database small, which increases performance and the
			  small size makes backups faster.
			* Work well in a hosting cluster if using a shared storage device.
			* More efficent to backup, due to ability to rsync or transfer increments.
			* No limits to the filesize, except for limits imposed by network connection speed and HTTP timeout.

	2. Upload all files into the MySQL database as binary blobs

		Advantages:
			* All the data is in a single location
			* Single location to backup
			* Easier security controls.
			* If you have a replicating MySQL setup, the files will be replicated
			  as well.

	Either way the file_uploads database is used to store information about the file, such as it's size
	and filename, but the actual data will be pulled from the chosen location.

	The folllowing configuration choice must be defined in the program's configuration file:
	
	$_GLOBAL["data_storage_method"]		= "database";
	or
	$_GLOBAL["data_storage_method"]		= "filesystem";
	$_GLOBAL["data_storage_location"]	= "data/default/";
*/



/*
	class file_information

	Provides functions for querying the database to get information about the uploaded
	file, such as it's filename, size and other information.
*/
class file_information
{
	var $file_id;	// ID number of the file record
	var $data;	// array holding information about the file.

	/*
		fetch_information_by_id($id)

		Loads the information for the file with the specified id number.

		Return codes:
		0	failure to find record
		1	success
	*/
	function fetch_information_by_id($id)
	{
		log_debug("file_information", "Executing fetch_information_by_id($id)");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT file_name, file_size, file_location FROM file_uploads WHERE id='$id' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			// fetch data
			$sql_obj->fetch_array();
			$this->data	= $sql_obj->data[0];
			$this->file_id	= $sql_obj->data[0]["id"];

			return 1;
		}

		return 0;
	}

	
	/*
		fetch_information_by_type($type, $customid)

		Loads the information for the file with the specified type and customid. This is used
		by functions which don't know the ID of the file, but do know the ID of the record
		that the file belongs to. (eg: the journal functions)

		Return codes:
		0	failure to find record
		1 	success
	*/

	function fetch_information_by_type($type, $customid)
	{
		log_debug("file_information", "Executing fetch_information_by_type($type, $customid)");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, file_name, file_size, file_location FROM file_uploads WHERE type='$type' AND customid='$customid' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			// fetch data
			$sql_obj->fetch_array();
			
			$this->data	= $sql_obj->data[0];
			$this->file_id	= $sql_obj->data[0]["id"];

			return 1;
		}

		return 0;
	}


	/*
		render_filedata()
		
		This function outputs all the data for the file, including content headers. This function
		should be called by download scripts that output nothing other than this function.

		NOTE: fetch_information_by_SOMETHING must be called to load required database
		before executing this function

		Return codes:
		0	failure
		1	success
	*/
		
	function render_filedata()
	{
		log_debug("file_information", "Executing render_filedata()");

		// check that values required are provided
		// if there were not and we didn't have this check, we would see some very weird bugs.
		if (!$this->file_id || !$this->data["file_size"] || !$this->data["file_name"] || !$this->data["file_location"])
		{
			log_debug("file_information", "Error: function fetch_information_by_SOMETHING must be executed before function render_filedata");
			return 0;
		}


		/*
			Setup HTTP headers we need
		*/
		
		// required for IE, otherwise Content-disposition is ignored
		if (ini_get('zlib.output_compression'))
			ini_set('zlib.output_compression', 'Off');

		
		// set the relevant content type
		$file_extension = strtolower(substr(strrchr($this->data["file_name"],"."),1));

		switch ($file_extension)
		{
			case "pdf": $ctype="application/pdf"; break;
			case "exe": $ctype="application/octet-stream"; break;
			case "zip": $ctype="application/zip"; break;
			case "doc": $ctype="application/msword"; break;
			case "xls": $ctype="application/vnd.ms-excel"; break;
			case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
			case "gif": $ctype="image/gif"; break;
			case "png": $ctype="image/png"; break;
			case "jpeg":
			case "jpg": $ctype="image/jpg"; break;
			default: $ctype="application/force-download";
		}
		
		header("Pragma: public"); // required
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false); // required for certain browsers 
		header("Content-Type: $ctype");
		
		header("Content-Disposition: attachment; filename=\"".basename($this->data["file_name"])."\";" );
		header("Content-Transfer-Encoding: binary");
		
		// tell the browser how big the file is (in bytes)
		// most browers seem to ignore this, but it's vital in order to make IE 7 work.
		header("Content-Length: ". $this->data["file_size"] ."");

		
		
		/*
			Output File Data

			Each file in the DB has a field to show where the file is located, so if a user
			has some files on disk and some in the DB, we can handle it accordingly.
		*/

		log_debug("file_information", "Fetching file ". $this->file_id ." from location ". $this->data["file_location"] ."");
		
		if ($this->data["file_location"] == "db")
		{
			// get file from the database
			print sql_get_singlevalue("SELECT data as value WHERE id='". $this->file_id ."'");
		}
		else
		{
			// get file from filesystem
			$file_path = $_GLOBAL["data_storage_location"] . "/". $this->file_id;
			
			if (file_exists($file_path)
			{
				readfile($file_path);
			}
			else
			{
				print "FATAL ERROR: File $file_path is missing or inaccessible.";
			}
		}
	}


	
} // end of file_information class








	
?>
