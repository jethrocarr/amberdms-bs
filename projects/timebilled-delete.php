<?php
/*
	projects/timebilled-delete.php
	
	access: projects_write

	Allows the deletion of a deletion page.
*/

if (user_permissions_get('projects_write'))
{
	$id = $_GET["projectid"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Project Details";
	$_SESSION["nav"]["query"][]	= "page=projects/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Project Phases";
	$_SESSION["nav"]["query"][]	= "page=projects/phases.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Timebooked";
	$_SESSION["nav"]["query"][]	= "page=projects/timebooked.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Timebilled/Grouped";
	$_SESSION["nav"]["query"][]	= "page=projects/timebilled.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=projects/timebilled.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Project Journal";
	$_SESSION["nav"]["query"][]	= "page=projects/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Project";
	$_SESSION["nav"]["query"][]	= "page=projects/delete.php&id=$id";


	function page_render()
	{
		$projectid	= security_script_input('/^[0-9]*$/', $_GET["projectid"]);
		$groupid	= security_script_input('/^[0-9]*$/', $_GET["groupid"]);


		/*
			Perform verification tasks
		*/
		$error = 0;
		
		// check that the specified project actually exists
		$sql_obj		= New sql_query;
		
		$sql_obj->string	= "SELECT id FROM `projects` WHERE id='$projectid'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			print "<p><b>Error: The requested project does not exist. <a href=\"index.php?page=projects/projects.php\">Try looking for your project on the project list page.</a></b></p>";
			$error = 1;
		}
		else
		{
			// are we editing an existing group? make sure it exists and belongs to this project
			$sql_group_obj		= New sql_query;
			$sql_group_obj->string	= "SELECT projectid, locked FROM time_groups WHERE id='$groupid' LIMIT 1";
			$sql_group_obj->execute();

			if (!$sql_group_obj->num_rows())
			{
				print "<p><b>Error: The requested time group does not exist.</b></p>";
				$error = 1;
			}
			else
			{
				$sql_group_obj->fetch_array();
				if ($sql_group_obj->data[0]["projectid"] != $projectid)
				{
					print "<p><b>Error: The requested time group does not match the provided project ID. Potential application bug?</b></p>";
					$error = 1;
				}
					
			}
		}

	
		/*
			Display Form
		*/
		if (!$error)
		{
			/*
				Title + Summary
			*/
			
			print "<h3>DELETE TIME GROUP</h3><br>";
			print "<p>This page allows you to delete a time group. Once deleted, this action is irreverable.</p>";
			

			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "timebilled_delete";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "projects/timebilled-delete-process.php";
			$form->method = "post";
		
		
			// general
			$structure = NULL;
			$structure["fieldname"] 	= "name_group";
			$structure["type"]		= "text";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] 	= "name_customer";
			$structure["type"]		= "text";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] 	= "description";
			$structure["type"]		= "text";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] 	= "code_invoice";
			$structure["type"]		= "text";
			$form->add_input($structure);
	
			$structure = NULL;
			$structure["fieldname"] 	= "delete_confirm";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Yes, I wish to delete this time group and realise that once deleted the data can not be recovered.";
			$form->add_input($structure);

		


			// hidden values
			$structure = NULL;
			$structure["fieldname"]		= "projectid";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $projectid;
			$form->add_input($structure);
			
			$structure = null;
			$structure["fieldname"]		= "groupid";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $groupid;
			$form->add_input($structure);
		
			
			// submit button
			$structure = NULL;
			$structure["fieldname"] 	= "submit";

			if ($sql_group_obj->data[0]["locked"])
			{
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<i>This time group has now been locked and can no longer be adjusted - if you need to make changes, you will need to remove this time group from the invoice it belongs to.</i>";
			}
			else
			{
				$structure["type"]		= "submit";
				$structure["defaultvalue"]	= "delete";
			}
			
			$form->add_input($structure);

			


			// fetch the form data if editing
			$form->sql_query = "SELECT name_group, description, account_ar.code_invoice, customers.name_customer FROM time_groups LEFT JOIN customers ON customers.id = time_groups.customerid LEFT JOIN account_ar ON account_ar.id = time_groups.invoiceid WHERE time_groups.id='$groupid' LIMIT 1";
			$form->load_data();



			/*
				Display the form
			*/

			$form->subforms["timebilled_details"]	= array("name_group", "name_customer", "code_invoice", "description");
			$form->subforms["hidden"]		= array("projectid", "groupid");
			$form->subforms["submit"]		= array("delete_confirm", "submit");

			$form->render_form();


		} // end if valid options

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
