<?php
/*
	services/cdr-rates-import-process.php

	access: services_write

	Verifies and parses imported CSV file.
*/

//inclues
require("../include/config.php");
require("../include/amberphplib/main.php");
require("../include/services/inc_services.php");
require("../include/services/inc_services_cdr.php");


if (user_permissions_get("services_write"))
{
	/*
		Load Data
	*/

	$obj_rate_table						= New cdr_rate_table;
	$obj_rate_table->id					= @security_form_input_predefined("int", "id_rate_table", 1, "");
	$cdr_import_mode					= @security_form_input_predefined("any", "cdr_rate_import_mode", 1, "");




	/*
		Verify valid rate table
	*/
	if (!$obj_rate_table->verify_id())
	{
		log_write("error", "process", "The CDR rate table you have attempted to edit - ". $obj_rate_table->id ." - does not exist in this system.");
	}



	/*
		Verify File Upload
	*/

	$file_obj = New file_storage;
	$file_obj->verify_upload_form("cdr_rate_import_file", array("csv", "zip"));
   



	/*
		Handle Errors
	*/
    
	if (error_check())
	{
		header("Location: ../index.php?page=services/cdr-rates-import.php&id=". $obj_rate_table->id );
		exit(0);
	}
	else
	{
		error_clear();

		/*
			Load the file
		*/

		$rate_table = array();

		switch (format_file_extension($_FILES["cdr_rate_import_file"]["name"]))
		{
			case "zip":
				/*
					ZIP files can be uploaded containing multiple CSV files for import and processing.

					PHP makes this easy, by allowing us to open the temporary uploaded ZIP file
					and then reading each file out of the ZIP without needing for first uncompress onto the filesystem.


					In *theory* this should offer protection against ZIP bombs, since files aren't being written to disk - if a
					malicious user uploads a huge file, it will simply consume RAM for the PHP process until the process hits server
					limits and terminates.

					Worst case, the user could DOS the server by repeatadly uploading ZIP files to impact on performance but this is
					nowhere near as useful an attack as consuming all available disk space.
				*/
				
				log_write("debug", "process", "Reading in uploaded ZIP file");

				if (class_exists("ZipArchive"))
				{
					$zip = new ZipArchive;

					$res = $zip->open($_FILES["cdr_rate_import_file"]["tmp_name"]);

					if ($res === TRUE)
					{
		    				for ($i = 0; $i < $zip->numFiles; $i++)
						{
							// extract zip file information
					        	$filename = $zip->getNameIndex($i);
					        	$fileinfo = pathinfo($filename);

							log_write("debug", "process", "Reading in file $filename from uploaded ZIP archive");

							// standard geographic file
							$handle = fopen("zip://". $_FILES["cdr_rate_import_file"]["tmp_name"] ."#".$filename, "r");

							if (!$handle)
							{
								log_write("debug", "process", "Unable to read ZIP file $filename");
								break;
							}


							if ($cdr_import_mode == "cdr_import_mode_regular")
							{
								/*
									Importing standard CSV files, read them all in and add to array structure

									Note: This import function makes the assumption that all the CSV files are the same column
									format, if they aren't some columns may get mixed, which can't be avoided.
								*/

								log_write("notification", "process", "Imported file $filename");


								// read data into array
								$i = 0;

								while ($data = fgetcsv($handle, 1000, ",")) 
								{
									// count the number of entries in the row
									$num_entries = count($data);
									for ($j=0; $j<$num_entries; $j++)
									{
										// place the information into a 2 dimensional array
										$rate_table[$i][$j] = $data[$j];
									}
									$i++;
								}


							}
							elseif ($cdr_import_mode == "cdr_import_mode_nz_NAD")
							{
								/*
									Importing NAD, we need to read in only the files we actually care about. Some files
									differ from others, and we have to handle these formatting differences here.

									Desired Structure:
	
									Region | Prefix | Applicant (Organisation) | Status	| LCArea	| LICA		| Date	    | Notes
									------------------------------------------------------------------------------------------------------------
									09     | 123	| Example Corp Ltd	   | Assigned	| Auckland	| Auckland	| DD-Mmm-YY | Assigned for residential users
									09     | 124	| Example Corp Ltd	   | Assigned	| 		| Auckland	| DD-Mmm-YY | Assigned for commerical users.
								*/

								switch ($filename)
								{
									case "Geographic_Code_03.csv":
									case "Geographic_Code_04.csv":
									case "Geographic_Code_06.csv":
									case "Geographic_Code_07.csv":
									case "Geographic_Code_09.csv":
										/*
											Geographic Files meet the needs of the desired structure already.
										*/

										while ($data = fgetcsv($handle, 1000, ",")) 
										{
											// count the number of entries in the row
											$num_entries	= count($data);
											$data_new	= array();

											for ($j=0; $j<$num_entries; $j++)
											{
												$data_new[$j] = $data[$j];
											}

											$rate_table[] = $data_new;
										}

										log_write("notification", "process", "Imported file $filename");

									break;

									case "NonGeographic_Service_Codes_02XY.csv":
										/*
											Mobile Calls

											Import Structure:

											Region  | Prefix  | Applicant (Organisation) | Status	| Date      | Notes
											---------------------------------------------------------------------------
											02      | 10      | Example Mobile Network   | Assigned	| DD-Mmm-YY | Notes
										*/

										while ($data = fgetcsv($handle, 1000, ",")) 
										{
											$data_new	= array();

											$data_new[0]	= $data[0];
											$data_new[1]	= $data[1];
											$data_new[2]	= $data[2];
											$data_new[3]	= $data[3];
											$data_new[4]	= "Mobile";
											$data_new[5]	= "Mobile";
											$data_new[6]	= $data[4];
											$data_new[7]	= $data[5];

											$rate_table[] = $data_new;
										}

										log_write("notification", "process", "Imported file $filename");

									break;


									case "Special_Service_Codes_01XY.csv":
										/*
											Special Services Codes 01XY

											Import Structure:

											Region  | Prefix  | Applicant (Organisation) | Status	| ACat (??) | Date      | Notes
											---------------------------------------------------------------------------------------
											02      | 10      | Example Mobile Network   | Assigned	| 1         | DD-Mmm-YY | Notes
										*/

										while ($data = fgetcsv($handle, 1000, ",")) 
										{
											$data_new	= array();

											$data_new[0]	= $data[0];
											$data_new[1]	= $data[1];
											$data_new[2]	= $data[2];
											$data_new[3]	= $data[3];
											$data_new[4]	= "Special Services";
											$data_new[5]	= "Special Services";
											$data_new[6]	= $data[5];
											$data_new[7]	= $data[6];

											// special exceptions
											if ($data_new[0] == "01" && $data_new[1] == "72")
											{											
												$data_new[4]	= "National Directory";
												$data_new[5]	= "National Directory";
											}

											if ($data_new[0] == "01" && $data_new[1] == "8")
											{											
												$data_new[4]	= "International Directory";
												$data_new[5]	= "International Directory";
											}


											$rate_table[] = $data_new;
										}

										log_write("notification", "process", "Imported file $filename");

									break;

									case "Value_Added_Service_Codes_08XY.csv":
									case "Service_Provider_Prefixes_05XY.csv":
										/*
											These are special handoff codes from telecommunications providers - note that
											0800 is used as tollfree, but the 0800.... prefixes are not defined in this file,
											but in the seporate National_Toll_Free.csv file.

											0508 is a special tollfree prefix as well assigned to Telstraclear Only.

											Import Structure:

											Region  | Prefix  | Applicant (Organisation) | Status	| Date      | Notes
											---------------------------------------------------------------------------
											02      | 10      | Example Mobile Network   | Assigned	| DD-Mmm-YY | Notes	
										*/


										while ($data = fgetcsv($handle, 1000, ","))
										{
											$data_new	= array();

											$data_new[0]	= $data[0];
											$data_new[1]	= $data[1];
											$data_new[2]	= $data[2];
											$data_new[3]	= $data[3];
											$data_new[4]	= "Special Services";
											$data_new[5]	= "Special Services";
											$data_new[6]	= $data[4];
											$data_new[7]	= $data[5];

											// special exceptions
											if ($data_new[0] == "08" && $data_new[1] == "00")
											{											
												$data_new[4]	= "TollFree";
												$data_new[5]	= "TollFree";
											}

											if ($data_new[0] == "05" && $data_new[1] == "08")
											{
												$data_new[4]	= "TollFree";
												$data_new[5]	= "TollFree";
											}


											$rate_table[] = $data_new;
										}

										log_write("notification", "process", "Imported file $filename");

									break;

									case "National_Toll_Free.csv":
										/*
											TollFree 0800 Numbers

											Import Structure:

											Region  | Prefix  | Applicant (Organisation) | Status	| Date      | Notes
											---------------------------------------------------------------------------
											080     | 001     | Toll Free Provider       | Assigned	| DD-Mmm-YY | Notes
										*/


										while ($data = fgetcsv($handle, 1000, ",")) 
										{
											$data_new	= array();

											$data_new[0]	= $data[0];
											$data_new[1]	= $data[1];
											$data_new[2]	= $data[2];
											$data_new[3]	= $data[3];
											$data_new[4]	= "TollFree";
											$data_new[5]	= "TollFree";
											$data_new[6]	= $data[4];
											$data_new[7]	= $data[5];

											$rate_table[] = $data_new;
										}

										log_write("notification", "process", "Imported file $filename");

									break;

									default:
										log_write("notification", "process", "Excluding file $filename from import. (unwanted content)");
									break;
								}
								
							}
							else
							{
								log_write("error", "process", "Unable to handle ZIP files with CDR import mode of $cdr_import_mode");
							}

					        }        
		
						// close temporary file
						fclose($handle);
					}
					else
					{
						log_write("error", "process", "An error occured whilst attempting to unpack the uploaded zip file: $res");
					}
				}
				else
				{
					log_write("error", "process", "You must be running PHP version 5.2.0 or later with the ZIP extensions to enable ZIP import functionality.");
				}

			break;


			default:
			case "csv":
				/*
					Standard CSV Upload
				*/
				
				log_write("debug", "process", "Reading in uploaded CSV file");

				// check that file can be opened
				if ($handle = fopen($_FILES["cdr_rate_import_file"]["tmp_name"], "r"))
				{
					// read data into array
					$i = 0;
					while ($data = fgetcsv($handle, 1000, ",")) 
					{
						// count the number of entries in the row
						$num_entries = count($data);

						for ($j=0; $j<$num_entries; $j++)
						{
							// place the information into a 2 dimensional array
							$rate_table[$i][$j] = $data[$j];
						}

						$i++;
					}


					fclose($handle);
				}
				else
				{
					log_write("error", "process", "An unexpected error occured whilst trying to import the supplied file");
				}
			break;
		}

	
		if (error_check())
		{
			header("Location: ../index.php?page=services/cdr-rates-import.php&id=". $obj_rate_table->id );
			exit(0);
		}
		else
		{
			// save to session
			$_SESSION["csv_array"]	= $rate_table;

/*
			print "<pre>";
			print_r($rate_table);
			print "</pre>";

			die("Debug of Rate Table Import");
*/
		
			// refer to appropiate mode processing page
			if ($cdr_import_mode == "cdr_import_mode_regular")
			{
				header("Location: ../index.php?page=services/cdr-rates-import-csv.php&id=". $obj_rate_table->id );
				exit(0);
			}
			else
			{
				header("Location: ../index.php?page=services/cdr-rates-import-nad.php&id=". $obj_rate_table->id );
				exit(0);
			}
		}
	}
}
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
