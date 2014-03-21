<?php
/*
	admin/auditlock-process.php
	
	Access: admin only

	Locks various records based on the options provided by auditlock.php
*/


// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get("admin"))
{
	////// INPUT PROCESSING ////////////////////////

	$data["date_lock"]		= @security_form_input_predefined("date", "date_lock", 1, "");
	$data["date_lock_timestamp"]	= time_date_to_timestamp($data["date_lock"]);
	
	$data["lock_invoices_open"]	= @security_form_input_predefined("any", "lock_invoices_open", 0, "");
	$data["lock_journals"]		= @security_form_input_predefined("any", "lock_journals", 0, "");
	$data["lock_timesheets"]	= @security_form_input_predefined("any", "lock_timesheets", 0, "");



	//// PROCESS DATA ////////////////////////////


	if ($_SESSION["error"]["message"])
	{
		$_SESSION["error"]["form"]["auditlock"] = "failed";
		header("Location: ../index.php?page=admin/auditlock.php");
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
			Lock GL Transactions
		*/

		$sql_obj->string	= "UPDATE account_gl SET locked='1' WHERE locked='0' AND date_trans < '". $data["date_lock"] ."'";
		$sql_obj->execute();


		/*
			Lock Invoices
		*/

		$types = array("ar", "ap");

		foreach ($types as $type)
		{
			// depending on the user options, either only lock fully paid invoices, or
			// lock all invoices.

			if ($data["lock_invoices_open"])
			{
				// lock all invoices
				$sql_obj->string	= "UPDATE account_$type SET locked='1' WHERE locked='0' AND date_trans < '". $data["date_lock"] ."'";
				$sql_obj->execute();
			}
			else
			{
				// lock fully paid invoices only
				// (this code also locks any invoices with no items)
				$sql_obj->string	= "UPDATE account_$type SET locked='1' WHERE amount_total=amount_paid AND locked='0' AND date_trans < '". $data["date_lock"] ."'";
				$sql_obj->execute();
			}
		}


		/*
			(optional) Lock Journals
		*/

		if ($data["lock_journals"] == "on")
		{
			$sql_obj->string	= "UPDATE journal SET locked='1' WHERE locked='0' AND timestamp < '". $data["date_lock_timestamp"] ."'";
			$sql_obj->execute();
		}



		/*
			(optional) Lock Timesheets
		*/

		if ($data["lock_timesheets"] == "on")
		{
			$sql_obj->string	= "UPDATE timereg SET locked='1' WHERE date < '". $data["date_lock"] ."'";
			$sql_obj->execute();
		}



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An error occured whilst attempting to perform audit locking");
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "auditlock-process", "Successfully locked records up to ". $data["date_lock"] ."");
		}

		header("Location: ../index.php?page=admin/auditlock.php");
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
