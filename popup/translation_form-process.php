<?php
/*
	translation_form-process.php
	
	access: "translation_edit"  and "translation_add_new" group members

	Inputs user provided translations into the database.
*/

//includes
require("../include/config.php");
require("../include/amberphplib/main.php");


if (user_permissions_get("translation_edit")||user_permissions_get("translation_add_new"))
{
	/*
		Fetch Form/Session Data
	*/
	
	$num_trans = @security_form_input_predefined("int", "num_trans", 1, "");
	$language = $_SESSION["user"]["lang"];
	
	for ($i=1; $i<=$num_trans; $i++)
	{
		$row = "row_".$i;
		$raw_string = @security_form_input_predefined("any", "untranslated_".$i, 0, "");
		$translation = @security_form_input_predefined("any", "translated_".$i, 0, "");
		
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();
		
		//check that both fields are filled in
		if ($raw_string == "" && $translation == "")
		{
			//if both are blank, continue to next as this row was not filled in
			continue;
		}
		else
		{
			//if one field or the other is blank, give error
			if ($raw_string == "" || $translation == "")
			{
				error_flag_field($row);
				log_write("error", "page_output", "Both the untranslated phrase and the translation must be provided.");
				break;
			}
			else
			{
				//if label already exists in DB, check user has permission to edit
				$sql_obj->string = "SELECT id FROM language WHERE label='$raw_string' AND language='$language'";
				$sql_obj->execute();
				if ($sql_obj->num_rows())
				{
					if(!user_permissions_get("translation_edit"))
					{
						error_flag_field($row);
						log_write("error", "page_output", "You do not have permission to edit previously provided translations.");
						break;
					}
					else
					{
						$sql_obj->string = "UPDATE language SET translation = '$translation' WHERE label='$raw_string' AND language = '$language'";
						$sql_obj->execute();
					}
				}
				else
				{
				
					$sql_obj->string = "INSERT INTO language (language, label, translation) VALUES ('$language', '$raw_string', '$translation')";
					$sql_obj->execute();
				}
			}
		}
		
		
	}
	
	if (error_check())
	{
		$_SESSION["error"]["form"]["translation_form"] = "failed";
		$sql_obj->trans_rollback();
		header("Location: ../popup.php?page=popup/translation_form.php");
		exit(0);
	}
	else
	{
		$sql_obj->trans_commit();
		log_write("notification", "process", "Your translations have been saved");
		header("Location: ../popup.php?page=popup/translation_form.php");
		exit(0);
	}
}
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
	header("Location: ../popup.php?page=message.php");
	exit(0);
}

?>