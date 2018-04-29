<?php
/*
	popup/translation_form.php
	
	access: Those with translations permissions

	Allows users to enter translations
*/

class page_output
{
	var $id;
	var $obj_form;
	var $num_trans;

	function __construct()
	{
		$this->id = $_SESSION["user"]["id"];
	}


	function check_permissions()
	{
		if (user_permissions_get("translation_edit") || user_permissions_get("translation_add_new"))
		{
			return 1;
		}
	}

	function check_requirements()
	{
		// nothing to do
		return 1;
	}




	function execute()
	{
		/*
			Define form structure
		*/
		
		$this->obj_form = New form_input;
		$this->obj_form->formname 	= "translation_form";
		$this->obj_form->language 	= $_SESSION["user"]["lang"];

		$this->obj_form->action 	= "popup/translation_form-process.php";
		$this->obj_form->method 	= "post";

		$this->num_trans = 5;
		for ($i=1; $i<=$this->num_trans; $i++)
		{
			$structure = NULL;
			$structure["fieldname"] 	= "untranslated_".$i;
			$structure["type"]		= "input";
			$this->obj_form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "translated_".$i;
			$structure["type"]		= "input";
			$this->obj_form->add_input($structure);
		}
		
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Translations";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "num_trans";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->num_trans;
		$this->obj_form->add_input($structure);
		
		if (error_check())
		{
			$this->obj_form->load_data_error();
		}
	}



	function render_html()
	{
		// Title + Summary
		print "<h3>TRANSLATION</h3><br>";
		print "<p>Enter your translations in the form below.</p>";
		
		print "<p>Phrases surrounded by [[square braces]] have not yet been translated. Please enter the phrase inside the brackets in the \"Untranslated Phrase\" column.</p>";
		
		if($_SESSION["user"]["translation"]=="show_all_translatable_fields")
		{
			print "<p>Phrases surrounded by {{curly braces}} have had translations provided previously. Please enter the untranslated phrase in ((braces)) in the \"Untranslated Phrase\" column.</p>";
		}

	
		print "<form method=\"". $this->obj_form->method ."\" action=\"". $this->obj_form->action ."\" class=\"form_standard\">";
		print "<table class=\"form_table\" width=\"100%\">";
		print "<tr class=\"header\">";
		print "<td><strong>Untranslated Phrase</b></td>";
		print "<td><strong>Translation</b></td>";
		print "</tr>";
		
		for ($i=1; $i<=$this->num_trans; $i++)
		{
			if (isset($_SESSION["error"]["row_". $i ."-error"]))
			{
				print "<tr class=\"form_error\" id=\"row_".$i."\">";
			}
			else
			{
				print "<tr id=\"row_".$i."\">";
			}
				print "<td>";
				$this->obj_form->render_field("untranslated_".$i);
				print "</td>";
				print "<td>";
				$this->obj_form->render_field("translated_".$i);
				print "</td>";
			print "</tr>";
		}
		
		print "<tr><td colspan=\"2\"><br /></td></tr>";
		// form submit
		print "<tr class=\"header\">";
		print "<td colspan=\"2\"><b>Submit</b></td>";
		print "</tr>";
		
		$this->obj_form->render_row("submit");
		
		print "<tr><td colspan=\"2\"><br />";
		$this->obj_form->render_field("num_trans");
		print "</td></tr>";
	}

	
}

?>