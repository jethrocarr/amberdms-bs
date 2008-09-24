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

	The following configuration options need to be defined in the config table:
	name				value
	--
	DATA_STORAGE_METHOD		database


	or

	name				value
	--
	DATA_STORAGE_METHOD		filesystem
	DATA_STORAGE_LOCATION		data/default/
	
*/




/*
	class file_base
	
	Provides functions for querying the database to get information about the uploaded
	file, such as it's filename, size and other information.

	This class is included by the file_process class.
*/
class file_base
{
	var $config;	// array holding some desired configuration information
	var $data;	// array holding information about the file.

	/*
		file_base

		Constructor function
	*/
	function file_base()
	{
		$this->config["data_storage_method"]	= sql_get_singlevalue("SELECT value FROM config WHERE name='DATA_STORAGE_METHOD'");
		$this->config["data_storage_location"]	= sql_get_singlevalue("SELECT value FROM config WHERE name='DATA_STORAGE_LOCATION'");
	}


	/*
		fetch_information_by_id($id)

		Loads the information for the file with the specified id number.

		Return codes:
		0	failure to find record
		1	success
	*/
	function fetch_information_by_id($id)
	{
		log_debug("file_base", "Executing fetch_information_by_id($id)");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, customid, file_name, file_size, file_location FROM file_uploads WHERE id='$id' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			// fetch data
			$sql_obj->fetch_array();
			$this->data	= $sql_obj->data[0];

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
		log_debug("file_base", "Executing fetch_information_by_type($type, $customid)");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, customid, file_name, file_size, file_location FROM file_uploads WHERE type='$type' AND customid='$customid' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			// fetch data
			$sql_obj->fetch_array();
			
			$this->data	= $sql_obj->data[0];

			return 1;
		}

		return 0;
	}



	/*
		format_filesize_human()

		Returns a filesize in human readable format. (the raw filesize is in bytes)
	*/
	function format_filesize_human()
	{
		log_debug("file_base", "Executing format_filesize_human()");
	
		return format_size_human($this->data["file_size"]);
	}
	
	
} // end of file_base class





/*
	class file_process

	Functions for processing file information - outputting or uploading files
*/
class file_process extends file_base
{

	/*
		process_upload_from_from($fieldname)

		Uploads a file to the database - will replace an existing file if one already exists.

		Set $fieldname to the name of the form name of the field to upload

		Return Codes:
		0	failure
		#	ID number of the successfully uploaded file.
	*/
	function process_upload_from_form($fieldname)
	{
		log_debug("file_process", "Executing process_upload_from_form($fieldname)");


		// new upload - create DB place holder
		if (!$this->data["id"])
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO file_uploads (customid, type) VALUES ('". $this->data["customid"] ."', '". $this->data["type"] ."')";
			
			$sql_obj->execute();
			
			if (!$this->data["id"] = $sql_obj->fetch_insert_id())
			{
				log_debug("file_process", "Error: Failed to create DB entry for file upload.");
				return 0;
			}
		}

		// upload the data to the location chosen by the configuration
		if ($this->config["data_storage_method"] == "filesystem")
		{
			/*
				Upload file to configured location on filesystem
			*/
			$uploadname = $this->config["data_storage_location"] ."/". $this->data["id"];
			
			// move uploaded file to storage location
			if (!copy($_FILES[$fieldname]["tmp_name"],  $uploadname))
			{
				$_SESSION["error"]["message"][] = "Unable to upload file to storage location - possibly no write permissions.";

				log_debug("file_process", "Error: Failed to move file to storage location ($uploadname)");
				return 0;
			}
			
			$this->data["file_location"] = "fs";
		}
		elseif ($this->config["data_storage_method"])
		{
			/*
				Upload file to database

				We need to split the file into 64kb chunks, and add a new row to the file_upload_data table for	each chunk.
			*/

			if (!file_exists($_FILES[$fieldname]["tmp_name"]))
			{
				log_debug("file_process", "Uploaded file ". $_FILES[$fieldname]["tmp_name"] ." was not found and could not be loaded into the database");
				return 0;
			}
			else
			{

				// delete any existing files from the database
				$sql_obj = New sql_query;
				$sql_obj->string = "DELETE FROM file_upload_data WHERE fileid='". $this->data["id"] ."'";
				$sql_obj->execute();
			
				
				// open the file - read only & binary
        			$file_handle = fopen($_FILES[$fieldname]["tmp_name"], "rb");

			    	while (!feof($file_handle))
			   	{
					// make the data safe for MySQL, we don't want any
					// SQL injections from file uploads!
		        
					$binarydata = addslashes(fread($file_handle, 65535));


					// upload the row
					// note that the ID of the rows will increase, so if we sort the rows
					// in ascenting order, we will recieve the correct data.
					$sql_obj->string = "INSERT INTO file_upload_data (fileid, data) values ('". $this->data["id"] ."', '". $binarydata ."')";
					
					if (!$sql_obj->execute())
					{
						log_debug("file_process", "Error: Unable to insert binary data row.");
						return 0;
					}
				}

				// close the file
				fclose($file_handle);
			}
			

			$this->data["file_location"] = "db";
		}
		else
		{
			log_debug("file_process", "Error: Invalid data_storage_method (". $this->config["data_storage_method"] .") configured.");
			return 0;
		}

		// update database record
		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE file_uploads SET "
					."timestamp='". time() ."', "
					."file_name='". $this->data["file_name"] ."', "
					."file_size='". $this->data["file_size"] ."', "
					."file_location='". $this->data["file_location"] ."' "
					."WHERE id='". $this->data["id"] ."'";


		if ($sql_obj->execute())
		{
			return 1;
		}
		else
		{
			log_debug("file_process", "Error: Failed to update database record after file upload");
			return 0;
		}
		
	} // end of process_upload_from_form


	/*
		process_delete()

		Deletes the file specified by $this->data["id"].

		Return code:
		0	failure
		1	success
	*/
	function process_delete()
	{
		log_debug("file_process", "Excuting process_delete()");


		// Remove File
		
		if ($this->data["file_location"] == "db")
		{
			// delete file from the database
			$sql_obj = New sql_query;
			$sql_obj->string = "DELETE FROM file_upload_data WHERE fileid='". $this->data["id"] ."'";
			$sql_obj->execute();
		}
		else
		{
			// delete file from the filesystem
			$file_path = $this->config["data_storage_location"] . "/". $this->data["id"];
			@unlink($file_path);
		}


		// Remove DB entry		
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM file_uploads WHERE id='". $this->data["id"] . "'";
		
		if ($sql_obj->execute())
		{
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
		log_debug("file_process", "Executing render_filedata()");

		// check that values required are provided
		// if there were not and we didn't have this check, we would see some very weird bugs.
		if (!$this->data["id"] || !$this->data["file_size"] || !$this->data["file_name"] || !$this->data["file_location"])
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

		log_debug("file_information", "Fetching file ". $this->data["id"] ." from location ". $this->data["file_location"] ."");
		
		if ($this->data["file_location"] == "db")
		{
			/*
				Fetch file data from database
			*/
		
			// fetch a list of all the rows with file data from the file_upload_data directory
			$sql_obj = New sql_query;
			$sql_obj->string = "SELECT id FROM file_upload_data WHERE fileid='". $this->data["id"] ."' ORDER BY id";
			$sql_obj->execute();

			if (!$sql_obj->num_rows())
			{
				die("No data found for file". $this->data["id"] ."");
			}

			$sql_obj->fetch_array();

			// create an array of all the IDs
			$file_data_ids = array();
			foreach ($sql_obj->data as $data)
			{
				$file_data_ids[] = $data["id"];
			}

			
			// fetch the data for each ID
			foreach ($file_data_ids as $id)
			{
				$sql_obj = New sql_query;
				$sql_obj->string = "SELECT data FROM file_upload_data WHERE id='$id' LIMIT 1";
				$sql_obj->execute();
				$sql_obj->fetch_array();

				print $sql_obj->data[0]["data"];
			}
		}
		else
		{
			/*
				Output data from filesystem
			*/
			// get file from filesystem
			$file_path = $this->config["data_storage_location"] . "/". $this->data["id"];
			
			if (file_exists($file_path))
			{
				readfile($file_path);
			}
			else
			{
				die("FATAL ERROR: File ". $this->data["id"] . " $file_path is missing or inaccessible.");
			}
		}
	}

	
} // end of file_process class




?>
