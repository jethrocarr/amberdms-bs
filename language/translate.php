<?php
/*
	language/translate.php

	access:
		translation_edit
		translation_add_new

	Support file included by the primary application that defines the translation form/window for
	the popup application translation function
*/


if (user_permissions_get("devel_translate"))
{

	/*
		Design Form Structure
	*/

	// header
	$obj_form = New form_input;
	$obj_form->formname = "translate";
	$obj_form->language = $_SESSION["user"]["lang"];

	$obj_form->action = "";
	$obj_form->method = "none";
		

	// form elements
	$structure = NULL;
	$structure["fieldname"] 	= "trans_label";
	$structure["type"]		= "input";
	$structure["options"]["req"]	= "yes";
	$obj_form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"] 	= "trans_translation";
	$structure["type"]		= "input";
	$structure["options"]["req"]	= "yes";
	$obj_form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"] 	= "submit";
	$structure["type"]		= "message";
	$structure["defaultvalue"]	= "<p><a class=\"button\" id=\"trans_submit\" href=\"\">Save Changes</a></p>";
	$obj_form->add_input($structure);
	
	// define subforms
	$obj_form->subforms["translate"]	= array("trans_label", "trans_translation");
	$obj_form->subforms["submit"]		= array("submit");
		
	// fetch the form data
//	$obj_form->sql_query = "SELECT * FROM `customers` WHERE id='". $this->id ."' LIMIT 1";
//	$obj_form->load_data();


	/*
		Render Form
	*/

	print "<div id=\"trans_popup\">";
		print "<a id=\"trans_popup_close\">[X]</a>";

		print "<h3>". lang_trans("trans_form_title") ."</h3>";
		print "<p>". lang_trans("trans_form_desc") ."</p>";

		$obj_form->render_form();

	print "</div>";

	print "<div id=\"trans_popup_background\"></div>";
}


?>
