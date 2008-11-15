<?php
/*
	accounts/gl/delete.php
	
	access:	accounts_gl_write

	Allows an unwanted transaction to be deleted.
*/

if (user_permissions_get('accounts_gl_write'))
{
	$id = $_GET["id"];
	
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;	
	
	$_SESSION["nav"]["title"][]	= "Transaction Details";
	$_SESSION["nav"]["query"][]	= "page=accounts/gl/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Transaction";
	$_SESSION["nav"]["query"][]	= "page=accounts/gl/delete.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=accounts/gl/delete.php&id=$id";



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>DELETE TRANSACTION</h3><br>";
		print "<p>This page allows you to delete an unwanted transaction, provided that it hasn't been locked.</p>";

		$sql_trans_obj		= New sql_query;
		$sql_trans_obj->string	= "SELECT id, locked FROM `account_gl` WHERE id='$id'";
		$sql_trans_obj->execute();
		
		if (!$sql_trans_obj->num_rows())
		{
			print "<p><b>Error: The requested transaction does not exist. <a href=\"index.php?page=accounts/gl/gl.php\">Try looking for your transaction in the general ledger.</a></b></p>";
		}
		else
		{
			// we need some of the info later on
			$sql_trans_obj->fetch_array();

			
			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "transaction_delete";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "accounts/gl/delete-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "code_gl";
			$structure["type"]		= "text";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] 	= "description";
			$structure["type"]		= "text";
			$form->add_input($structure);


			// hidden
			$structure = NULL;
			$structure["fieldname"] 	= "id_transaction";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);
			
			
			// confirm delete
			$structure = NULL;
			$structure["fieldname"] 	= "delete_confirm";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Yes, I wish to delete this transaction and realise that once deleted the data can not be recovered.";
			$form->add_input($structure);



			/*
				Check that the transaction can be deleted
			*/

			// define submit field
			$structure = NULL;
			$structure["fieldname"] = "submit";

			if ($sql_trans_obj->data[0]["locked"])
			{
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<i>This transaction has now been locked and can not be deleted.</i>";
			}
			else
			{
				$structure["type"]		= "submit";
				$structure["defaultvalue"]	= "delete";
			}
					
			$form->add_input($structure);


			
			// define subforms
			$form->subforms["transaction_delete"]	= array("code_gl", "description");
			$form->subforms["hidden"]		= array("id_transaction");
			$form->subforms["submit"]		= array("delete_confirm", "submit");

			
			// fetch the form data
			$form->sql_query = "SELECT code_gl, description FROM `account_gl` WHERE id='$id' LIMIT 1";
			$form->load_data();

			// display the form
			$form->render_form();

		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
