<?php
/*
	services/cdr-rates-import-csv.php

	access:	services_write


	Takes the data in the session array imported by cdr-rates-import.php and allows the user
	to map the columns, before confirming data entry.

*/

require("include/services/inc_services.php");
require("include/services/inc_services_cdr.php");

class page_output
{
	var $obj_form;
	var $num_col;
	var $example_array;
	

	function page_output()
	{
		$this->obj_rate_table	= New cdr_rate_table;


		// fetch variables
		$this->obj_rate_table->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Rate Table Details", "page=services/cdr-rates-view.php&id=". $this->obj_rate_table->id ."");
		$this->obj_menu_nav->add_item("Rate Table Items", "page=services/cdr-rates-items.php&id=". $this->obj_rate_table->id ."");
		$this->obj_menu_nav->add_item("Rate Table Import", "page=services/cdr-rates-import.php&id=". $this->obj_rate_table->id ."", TRUE);
		$this->obj_menu_nav->add_item("Delete Rate Table", "page=services/cdr-rates-delete.php&id=". $this->obj_rate_table->id ."");
	}


	function check_permissions()
	{
		return user_permissions_get("services_write");
	}


	function check_requirements()
	{
		if (!$this->obj_rate_table->verify_id())
		{
			log_write("error", "page_output", "The supplied rate table ID ". $this->obj_rate_table->id ." does not exist");
			return 0;
		}

		return 1;
	}


	function execute()
	{
		/*
			Define fields and column examples
		*/
		$this->num_col	= count($_SESSION["csv_array"][0]);
		$values_array	= array("col_destination", "col_prefix", "col_cost_price", "col_sale_price");
		
		$this->obj_form			= New form_input;
		$this->obj_form->formname	= "cdr_rate_table_import_csv";
		

		// for each entry in the sub array, create a drop down menu
		for ($i=1; $i<=$this->num_col; $i++)
		{
			$name				= "column".$i;
			$structure 			= NULL;
			$structure["fieldname"]		= $name;
			$structure["type"]		= "dropdown";
			$structure["values"]		= $values_array;

			$this->obj_form->add_input($structure);
		}


		// import options
		$structure 				= NULL;
		$structure["fieldname"]			= "cdr_rate_import_mode";
		$structure["type"]			= "radio";
		$structure["values"]			= array("cdr_import_delete_existing", "cdr_import_update_existing");
		$structure["defaultvalue"]		= "cdr_import_update_existing";
		$this->obj_form->add_input($structure);

		$structure = form_helper_prepare_dropdownfromdb("rate_billgroup", "SELECT id, billgroup_name as label FROM cdr_rate_billgroups");
		$structure["options"]["req"]		= "yes";
		$structure["options"]["width"]		= "100";
		$this->obj_form->add_input($structure);

		$structure 				= NULL;
		$structure["fieldname"]			= "cdr_rate_import_cost_price";
		$structure["type"]			= "radio";
		$structure["values"]			= array("cdr_import_cost_price_use_csv", "cdr_import_cost_price_nothing");
		$this->obj_form->add_input($structure);

		$structure 				= NULL;
		$structure["fieldname"]			= "cdr_rate_import_sale_price";
		$structure["type"]			= "radio";
		$structure["values"]			= array("cdr_import_sale_price_use_csv", "cdr_import_sale_price_nothing", "cdr_import_sale_price_margin");
		$this->obj_form->add_input($structure);

		$structure 				= NULL;
		$structure["fieldname"]			= "cdr_rate_import_sale_price_margin";
		$structure["type"]			= "input";
		$structure["options"]["width"]		= "50";
		$this->obj_form->add_input($structure);




		// hidden fields
		$structure 				= NULL;
		$structure["fieldname"]			= "id_rate_table";
		$structure["type"]			= "hidden";
		$structure["defaultvalue"]		= $this->obj_rate_table->id;
		$this->obj_form->add_input($structure);

		$structure				= NULL;
		$structure["fieldname"]			= "num_cols";
		$structure["type"]			= "hidden";
		$structure["defaultvalue"]		= $this->num_col;
		$this->obj_form->add_input($structure);

		// submit
		$structure 				= NULL;
		$structure["fieldname"]			= "submit";
		$structure["type"]			= "submit";
		$structure["defaultvalue"]		= "submit";
		$this->obj_form->add_input($structure);
	



		/*
			populate an array of examples
			create one for each entry in the sub arrays
		*/
		for ($i=0; $i<$this->num_col; $i++)
		{		
		    //check for example in each array
		    //start from the bottom to find examples- this ensures more accurate data
		    //do not create an example if no data is present in any of the columns
		    for ($j=count($_SESSION["csv_array"])-1; $j>0; $j--)
		    {		    
			if ($_SESSION["csv_array"][$j][$i] != "")
			{
			    $this->example_array[$i+1]	= $_SESSION["csv_array"][$j][$i];
			    break;
			}
			
		    }
		}



		/*
			Load error data (if any)
		*/
		if (error_check())
		{
			$this->obj_form->load_data_error();
		}
		
	} 


	function render_html()
	{
		/*
			Header
		*/


		// Title + Summary
		print "<h3>CDR IMPORT COLUMN CLARIFICATION</h3><br>";
		print "<p>Please match the CSV file columns with the correct ones for importing and choose additional import options</p>";
	
		// display the form
		print "<form class=\"form_standard\" action=\"services/cdr-rates-import-csv-process.php\" method=\"post\" enctype=\"multipart/form-data\">";
		
		print "<table class=\"form_table\">";
		


		/*
			Field Assignment
		*/
		print "<tr class=\"header\">";
			print "<td><b>Column</b></td>";
			print "<td><b>Example</b></td>";
			print "<td><b>Field</b></td>";
		print "</tr>";

		for($i=1; $i<=$this->num_col; $i++)
		{
			if (isset($this->example_array[$i]))
			{
				$name = "column".$i;
				$name_error = $name."-error";

				if (isset($_SESSION["error"][$name_error]))
				{
					print "<tr class=\"form_error\">";
				}
				else
				{
					print "<tr id=\"".$name."\">";
				}

				print "<td>";
				print "Column ".$i;
				print "</td>";
				print "<td>";
				print $this->example_array[$i];
				print "</td>";
				print "<td>";
				$this->obj_form->render_field($name);
				print "</td>";
				print "</tr>";
			}
		 }




		/*
			Import Options
		*/
		print "<tr class=\"header\">";
			print "<td colspan=\"3\"><b>". lang_trans("cdr_rate_import_options") ."</b></td>";
		print "</tr>";

		if (isset($_SESSION["error"]["cdr_rate_import_mode"]))
		{
			print "<tr class=\"form_error\">";
		}
		else
		{
			print "<tr id=\"cdr_rate_import_mode\">";
		}

			print "<td colspan=\"2\">". lang_trans("cdr_rate_import_mode") ." *</td>";
			print "<td>";
			$this->obj_form->render_field("cdr_rate_import_mode");
			print "</td>";
		print "</tr>";


		if (isset($_SESSION["error"]["rate_billgroup"]))
		{
			print "<tr class=\"form_error\">";
		}
		else
		{
			print "<tr id=\"rate_billgroup\">";
		}

			print "<td colspan=\"2\">". lang_trans("rate_billgroup") ." *</td>";
			print "<td>";
			$this->obj_form->render_field("rate_billgroup");
			print "</td>";
		print "</tr>";

		if (isset($_SESSION["error"]["cdr_rate_import_cost_price"]))
		{
			print "<tr class=\"form_error\">";
		}
		else
		{
			print "<tr id=\"cdr_rate_import_mode\">";
		}

			print "<td colspan=\"2\">". lang_trans("cdr_rate_import_cost_price") ." *</td>";
			print "<td>";
			$this->obj_form->render_field("cdr_rate_import_cost_price");
			print "</td>";
		print "</tr>";
	
		if (isset($_SESSION["error"]["cdr_rate_import_sale_price"]))
		{
			print "<tr class=\"form_error\">";
		}
		else
		{
			print "<tr id=\"cdr_rate_import_sale_price\">";
		}

			print "<td colspan=\"2\">". lang_trans("cdr_rate_import_sale_price") ." *</td>";
			print "<td>";
			$this->obj_form->render_field("cdr_rate_import_sale_price");
			print "</td>";
		print "</tr>";

		if (isset($_SESSION["error"]["cdr_rate_import_sale_price_margin"]))
		{
			print "<tr class=\"form_error\">";
		}
		else
		{
			print "<tr id=\"cdr_rate_import_sale_price_margin\">";
		}

			print "<td colspan=\"2\">". lang_trans("cdr_rate_import_sale_price_margin") ."</td>";
			print "<td>";
			$this->obj_form->render_field("cdr_rate_import_sale_price_margin");
			print " %</td>";
		print "</tr>";






		/*
			Hidden & Submit
		*/
		print "<tr class=\"header\">";
			print "<td colspan=\"3\"><b>". lang_trans("submit") ."</b></td>";
		print "</tr>";
		    
		print "<tr id=\"submit\">";
			print "<td colspan=\"3\">";
			$this->obj_form->render_field("num_cols");
			$this->obj_form->render_field("id_rate_table");
			$this->obj_form->render_field("submit");
			print "</td>";
			print "</tr>";
		print "</table>";
		print "</form>";
	}	

} // end class page_output

?>
