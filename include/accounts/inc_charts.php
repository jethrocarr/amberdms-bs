<?php
/*
	include/accounts/inc_charts.php

	Contains various functions for easing working with charts.
*/


/*
	FUNCTIONS
*/


/*
	charts_form_prepare_acccountdropdown($fieldname, $menuname)

	Returns a structure for creating a form drop down of charts with suitable menu configurations.

	Values
	fieldname		Name of the form dropdown
	menuid/menuname		Either the ID or name (value) of the menu item. It is recommended
				to use the name for clarity of code and the ID will probably be phased out eventually.
*/
function charts_form_prepare_acccountdropdown($fieldname, $menu_name)
{
	log_debug("inc_charts", "Executing charts_form_prepare_accountdropdown($fieldname, $menu_name)");


	// see if we need to fetch the ID for the name
	// (see function comments - this will be phased out eventually)
	if (is_int($menu_name))
	{
		log_debug("inc_charts", "Obsolete: Use of menu ID rather than menu name");

		$menuid = $menu_name;
	}
	else
	{
		$menuid = sql_get_singlevalue("SELECT id as value FROM account_chart_menu WHERE value='$menu_name'");
	}


	// fetch list of suitable charts belonging to the menu requested.
	$sql_query	= "SELECT "
			."account_charts.id as id, "
			."account_charts.code_chart as label, "
			."account_charts.description as label1 "
			."FROM account_charts "
			."LEFT JOIN account_charts_menus ON account_charts_menus.chartid = account_charts.id "
			."WHERE account_charts_menus.menuid='$menuid'";
											
	$return = form_helper_prepare_dropdownfromdb($fieldname, $sql_query);

	// if we don't get any form data returned this means no charts with the required
	// permissions exist in the database, so we need to return a graceful error.
	if (!$return)
	{
		$structure = NULL;
		$structure["fieldname"]			= $fieldname;
		$structure["type"]			= "text";
		$structure["defaultvalue"]		= "No suitable charts avaliable";

		return $structure;
	}
	else
	{
		return $return;
	}
	
}



?>
