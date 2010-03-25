<?php
/*
	admin/templates-process.php
	
	Access: admin only

	Updates the selected templates.
*/


// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get("admin"))
{
	////// INPUT PROCESSING ////////////////////////


	// fetch all the data
	$data["ar_invoice_tex"]			= @security_form_input_predefined("int", "ar_invoice_tex", 1, "Please select a valid AR invoice.");


	// check that the returned ID belongs to the right template type
	$obj_sql		= New sql_query;
	$obj_sql->string	= "SELECT id FROM templates WHERE template_type IN('ar_invoice_tex', 'ar_invoice_htmltopdf') AND id='". $data["ar_invoice_tex"] ."'";
	$obj_sql->execute();

	if (!$obj_sql->num_rows())
	{
		log_write("error", "process", "The provided ID (". $data["ar_invoice_tex"] .") does not match an ar_invoice_tex or ar_invoice_htmltopdf template type.");
	}


	//// PROCESS DATA ////////////////////////////


	if ($_SESSION["error"]["message"])
	{
		$_SESSION["error"]["form"]["config"] = "failed";
		header("Location: ../index.php?page=admin/config.php");
		exit(0);
	}
	else
	{
		$_SESSION["error"] = array();


		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();

	

		/*
			Update the template selection
		*/

		$sql_obj->string = "UPDATE templates SET active='0' WHERE template_type='ar_invoice_tex'";
		$sql_obj->execute();

		$sql_obj->string = "UPDATE templates SET active='1' WHERE id='". $data["ar_invoice_tex"] ."' LIMIT 1";
		$sql_obj->execute();


	
		/*
			Commit
		*/
		
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An error occured whilst updating configuration, no changes have been applied.");
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "process", "Template selection updated successfully");
		}

		header("Location: ../index.php?page=admin/templates.php");
		exit(0);


	} // if valid data input
	
	
} // end of "is user logged in?"
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
