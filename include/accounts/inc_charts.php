<?php
/*
	include/accounts/inc_charts.php

	Contains various functions for easing working with charts.
*/


/*
	FUNCTIONS
*/


/*
	charts_form_prepare_acccountdropdown($fieldname, $menuid)

	Returns a structure for creating a form drop down of charts with suitable menu configurations.
*/
function charts_form_prepare_acccountdropdown($fieldname, $menuid)
{

	$sql_query	= "SELECT"
			."account_charts.id as id, "
			."account_charts.code_chart as label, "
			."account_charts.description as label1 "
			."FROM account_charts "
			."LEFT JOIN account_charts_menus ON account_charts_menus.chartid = account_charts.id "
			."WHERE account_charts_menus.menuid='$menuid'");
											
	return form_helper_prepare_dropdownfromdb($fieldname, $sql_query);
}



?>
