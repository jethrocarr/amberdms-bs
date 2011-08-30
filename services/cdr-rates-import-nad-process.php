<?php
/*
	services/cdr-rates-import-nat-process.php
	
	access: services_write

	Takes NAD import options/settings and the uploaded NAD data and imports
	into the CDR rate table.
*/

require("../include/config.php");
require("../include/amberphplib/main.php");

require("../include/services/inc_services.php");
require("../include/services/inc_services_cdr.php");


if (user_permissions_get("services_write"))
{
	/*
		Fetch Form/Session Data
	*/

	$obj_rate_table						= New cdr_rate_table;
	$obj_rate_table->id					= @security_form_input_predefined("int", "id_rate_table", 1, "");

	$data["nad_country_prefix"]				= @security_form_input_predefined("int", "nad_country_prefix", 0, "");
	$data["nad_default_destination"]			= @security_form_input_predefined("any", "nad_default_destination", 0,"");

	$data["cdr_rate_import_mode"]				= @security_form_input_predefined("any", "cdr_rate_import_mode", 1, "");

	$data["nad_price_cost_national"]			= @security_form_input_predefined("float", "nad_price_cost_national", 0, "");
	$data["nad_price_sale_national"]			= @security_form_input_predefined("float", "nad_price_sale_national", 0, "");
	$data["nad_price_cost_mobile"]				= @security_form_input_predefined("float", "nad_price_cost_mobile", 0, "");
	$data["nad_price_sale_mobile"]				= @security_form_input_predefined("float", "nad_price_sale_mobile", 0, "");
	$data["nad_price_cost_directory_national"]		= @security_form_input_predefined("float", "nad_price_cost_directory_national", 0, "");
	$data["nad_price_sale_directory_national"]		= @security_form_input_predefined("float", "nad_price_sale_directory_national", 0, "");
	$data["nad_price_cost_directory_international"]		= @security_form_input_predefined("float", "nad_price_cost_directory_international", 0, "");
	$data["nad_price_sale_directory_international"]		= @security_form_input_predefined("float", "nad_price_sale_directory_international", 0, "");
	$data["nad_price_cost_tollfree"]			= @security_form_input_predefined("float", "nad_price_cost_tollfree", 0, "");
	$data["nad_price_sale_tollfree"]			= @security_form_input_predefined("float", "nad_price_sale_tollfree", 0, "");
	$data["nad_price_cost_special"]				= @security_form_input_predefined("float", "nad_price_cost_special", 0, "");
	$data["nad_price_sale_special"]				= @security_form_input_predefined("float", "nad_price_sale_special", 0, "");


	/*
		Error Handling
	*/


	// verify valid rate table
	if (!$obj_rate_table->verify_id())
	{
		log_write("error", "process", "The CDR rate table you have attempted to edit - ". $obj_rate_table->id ." - does not exist in this system.");
	}


	// TODO: some sort of NAD validation logic here?



	/*
		Process Data
	*/

	if (error_check())
	{
		$_SESSION["error"]["form"]["cdr_import_rate_table_nad"] = "failed";

		header("Location: ../index.php?page=services/cdr-rates-import-nad.php&id=". $obj_rate_table->id);
		exit(0);
	}
	else
	{
		/*
			Read in CSV Data


			Structure of Geographic Files is:

			Region | Prefix | Applicant (Organisation) | Status	| LCArea	| LICA		| Date	    | Notes
			------------------------------------------------------------------------------------------------------------
			09     | 123	| Example Corp Ltd	   | Assigned	| Auckland	| Auckland	| DD-Mmm-YY | Assigned for residential users
			09     | 124	| Example Corp Ltd	   | Assigned	| 		| Auckland	| DD-Mmm-YY | Assigned for commerical users.


			*  There are situations where the LCAArea or LICA might not exist, however one should always exist for
			   any status == assigned call codes - any other status can not result in billable calls, so can be
			   safely excluded.

			   We take the LICA, unless it is lacking, in which case we take the LCArea.

			*  We strip the prefix zero from the region.

			* Price is assiged based on the value of LICA

		*/


		$columns		= array();

		$columns[0]		= "prefix_region";
		$columns[1]		= "prefix_area";
		$columns[2]		= "";
		$columns[3]		= "status";
		$columns[4]		= "lcarea";
		$columns[5]		= "lica";
		$columns[6]		= "";

		$csv_array		= $_SESSION["csv_array"];

		$import_array_raw	= array();		// temp working space
		$import_array		= array();		// final import data

		for ($i=0; $i < count($csv_array); $i++)
		{
			for ($j=0; $j < count($csv_array[0]); $j++)
			{
				/*
					Import Each Row
				*/

				switch ($columns[ $j ])
				{
					// prefix codes
					case "prefix_region":
						$import_array_raw[$i]["prefix_region"]	= $csv_array[$i][$j];
					break;

					case "prefix_area":
						$import_array_raw[$i]["prefix_area"]	= $csv_array[$i][$j];
					break;


					// status
					case "status":
						$import_array_raw[$i]["status"]		= $csv_array[$i][$j];
					break;


					// we need to get which ever area field is set
					case "lcarea":
						$import_array_raw[$i]["lcarea"]	= $csv_array[$i][$j];
					break;

					case "lica":
						$import_array_raw[$i]["lica"]	= $csv_array[$i][$j];
					break;

					default:
						// nothing todo
					break;

				} // end of switch columns


			} // end of column loop


			// handle pricing
			// we check the LICA against known pricing groups and set, otherwise fall back to national
			if (!empty($import_array_raw[$i]["lica"]))
			{
				switch ($import_array_raw[$i]["lica"])
				{
					case "Mobile":
						$import_array_raw[$i]["nad_price_cost"]		= $data["nad_price_cost_mobile"];
						$import_array_raw[$i]["nad_price_sale"]		= $data["nad_price_sale_mobile"];
						$import_array_raw[$i]["billgroup"]		= "3"; // Mobile
					break;

					case "TollFree":
						$import_array_raw[$i]["nad_price_cost"]		= $data["nad_price_cost_tollfree"];
						$import_array_raw[$i]["nad_price_sale"]		= $data["nad_price_sale_tollfree"];
						$import_array_raw[$i]["billgroup"]		= "2"; // National
					break;

					case "Special Services":
						$import_array_raw[$i]["nad_price_cost"]		= $data["nad_price_cost_special"];
						$import_array_raw[$i]["nad_price_sale"]		= $data["nad_price_sale_special"];
						$import_array_raw[$i]["billgroup"]		= "2"; // National
					break;

					case "National Directory":
						$import_array_raw[$i]["nad_price_cost"]		= $data["nad_price_cost_directory_national"];
						$import_array_raw[$i]["nad_price_sale"]		= $data["nad_price_sale_directory_national"];
						$import_array_raw[$i]["billgroup"]		= "2"; // National
					break;

					case "International Directory":
						$import_array_raw[$i]["nad_price_cost"]		= $data["nad_price_cost_directory_international"];
						$import_array_raw[$i]["nad_price_sale"]		= $data["nad_price_sale_directory_international"];
						$import_array_raw[$i]["billgroup"]		= "4"; // International
					break;

					case "National":
					default:
						$import_array_raw[$i]["nad_price_cost"]		= $data["nad_price_cost_national"];
						$import_array_raw[$i]["nad_price_sale"]		= $data["nad_price_sale_national"];
						$import_array_raw[$i]["billgroup"]		= "2"; // National
					break;
				}
			}
			else
			{
				$import_array_raw[$i]["nad_price_cost"]		= $data["nad_price_cost_national"];
				$import_array_raw[$i]["nad_price_sale"]		= $data["nad_price_sale_national"];
				$import_array_raw[$i]["billgroup"]		= "2"; // National
			}




			/*
				Process Row

				We process the data and only turn assigned call prefixes into
				records for the rate table.
			*/

			if (strtolower($import_array_raw[$i]["status"]) == "assigned")
			{
				$import_row = array();


				// column prefix
				$import_row["col_prefix"] = $data["nad_country_prefix"] . ltrim($import_array_raw[$i]["prefix_region"], "0") . $import_array_raw[$i]["prefix_area"];


				// take the region name as the destination
				if ($import_array_raw[$i]["lica"])
				{
					$import_row["col_destination"] = $import_array_raw[$i]["lica"];
				}
				elseif (empty($import_array_raw[$i]["lica"]) && !empty($import_array_raw[$i]["lcarea"]))
				{
					$import_row["col_destination"] = $import_array_raw[$i]["lcarea"];
				}
				else
				{
					$import_row["col_destination"] = $data["nad_default_destination"];
				}


				// pricing
				$import_row["nad_price_cost"] = $import_array_raw[$i]["nad_price_cost"];
				$import_row["nad_price_sale"] = $import_array_raw[$i]["nad_price_sale"];
				$import_row["rate_billgroup"] = $import_array_raw[$i]["billgroup"];


				$import_array[] = $import_row;
			}


		} // end of row loop

		$import_array_raw	= array();


		/*
			Import rates
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		// fetch or delete existing data
		if ($data["cdr_rate_import_mode"] == "cdr_import_delete_existing")
		{
			log_write("debug", "process", "Deleting existing rates");

			// delete all the current rates, except default or local
			$sql_obj->string	= "DELETE FROM cdr_rate_tables_values WHERE id_rate_table='". $obj_rate_table->id ."' AND rate_prefix!='DEFAULT' AND rate_prefix!='LOCAL'";
			$sql_obj->execute();
		}
		else
		{
			log_write("debug", "process", "Updating existing rates");

			// delete current rates that have the same prefix as any of the new rates - this causes the import to
			// override existing rates, but not delete any other ones that aren't specified in the imported
		
			foreach ($import_array as $import_row)
			{
				// delete current rate item
				$sql_obj->string	= "DELETE FROM cdr_rate_tables_values WHERE id_rate_table='". $obj_rate_table->id ."' AND rate_prefix='". $import_row["col_prefix"] ."' LIMIT 1";
				$sql_obj->execute();
			}

		} // end if delete current

	

		// run through and insert rates
		foreach ($import_array as $import_row)
		{
			$sql_obj->string	= "INSERT INTO cdr_rate_tables_values (
										id_rate_table,
										rate_prefix,
										rate_description,
										rate_billgroup,
										rate_price_sale,
										rate_price_cost)
										VALUES (
										'". $obj_rate_table->id ."',
										'". $import_row["col_prefix"] ."',
										'". $import_row["col_destination"] ."',
										'". $import_row["rate_billgroup"] ."',
										'". $import_row["nad_price_sale"] ."',
										'". $import_row["nad_price_cost"] ."')";
			$sql_obj->execute();
		}
	

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An database error occured whilst trying to import the supplied file.");
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "process", "Call rates successfully imported! Please review to ensure there have been no inaccurances or mistakes imported.");
		
			// clear session stuff
			$_SESSION["error"]	= NULL;
			$_SESSION["csv_array"]	= NULL;
			$_SESSION["csv_mode"]	= NULL;
		}



		// return
		header("Location: ../index.php?page=services/cdr-rates-items.php&id=". $obj_rate_table->id);
		exit(0);
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
