<?php
/*
	accounts/gl/delete-process.php

	access: account_gl_write

	Deletes a transaction, provided that it has not been locked.
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_gl.php");



if (user_permissions_get('accounts_gl_write'))
{

	$obj_gl = New gl_transaction;


	/*
		Import POST Data
	*/

	$obj_gl->id				= @security_form_input_predefined("int", "id_transaction", 1, "");

	// these exist to make error handling work right
	$obj_gl->data["code_gl"]		= @security_form_input_predefined("any", "code_gl", 0, "");
	$obj_gl->data["description"]		= @security_form_input_predefined("any", "description", 0, "");

	// confirm deletion
	$obj_gl->data["delete_confirm"]		= @security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");



	/*
		Error Handling
	*/

	// make sure the transaction actually exists
	if (!$obj_gl->verify_id())
	{
		log_write("error", "process", "The transaction you have attempted to edit - ". $obj_gl->id ." - does not exist in this system.");
	}

	if ($obj_gl->check_delete_lock())
	{
		log_write("error", "process", "This transaction can not be deleted, because it is locked");
	}


	// return to input page in event of an error
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["transaction_delete"] = "failed";
		header("Location: ../../index.php?page=accounts/gl/delete.php&id=". $obj_gl->id);
		exit(0);
	}


	/*
		Action
	*/

	$obj_gl->action_delete();

	header("Location: ../../index.php?page=accounts/gl/gl.php");
	exit(0);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
