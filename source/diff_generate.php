<?php
/*
	source/diff_generate.php
	
	access: all logged in users

	(we don't provide access to public users, since that could put performance strains on the server and
	 might reveal code that can't be made public)

	Generates a diff between offical Amberdms source code and the source code currently running on this
	server and provides a form to save the patch.
*/

class page_output
{
	var $tmpdir;
	var $obj_menu_nav;
	var $obj_form;


	function page_output()
	{
		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Source Download", "page=source/getsource.php");
		$this->obj_menu_nav->add_item("Generate Patch", "page=source/diff_generate.php", TRUE);
	}



	function check_permissions()
	{
		return user_online();
	}

	function check_requirements()
	{
		// nothing to do
		return 1;
	}

	function execute()
	{
		if ($_SESSION["error"]["message"])
		{
			// an error occured during the form submittal - in this case, we don't need to regenerate the patch
		}
		else
		{

			/*
				GENERATE DIFF

				This works in 4 main steps:
				1. Download Amberdms Source to a temp location.
				2. Extract source to a temp location.
				3. Copy all source from running version to temp location.
				4. Generate diff between offical source and currently running source.
			*/

			$path_tmpdir	= sql_get_singlevalue("SELECT value FROM config WHERE name='PATH_TMPDIR'");

			$this->tmpdir	= dir_generate_tmpdir();



			/*
				Download Source Code

				Check if the source already exists in the temporary directory, if not, then download from Amberdms.
			*/

			// structure of all versions and md5sums
			$amberdms_source["1.3.0"]["url"]	= "http://www.amberdms.com/repo/beta/";
			$amberdms_source["1.3.0"]["file"]	= "amberdms-bs-1.3.0.beta1.tar.bz2";
			
			$version = $GLOBALS["config"]["app_version"];

			if (!$amberdms_source[ $version ])
			{
				log_write("error", "execute", "Unknown version of Amberdms Billing System, unable to generate diff!");
				return 0;
			}

			if (file_exists("$path_tmpdir/". $amberdms_source[ $version ]["file"]))
			{
				log_write("debug", "execute", "Existing file found in $path_tmpdir/");
				system("cp $path_tmpdir/". $amberdms_source[ $version ]["file"] ." ". $this->tmpdir ."/orig.tar.bz2");
			}
			else
			{
				/*
					Need to download source code from Amberdms servers
				*/
				
				log_write("debug", "execute", "No source in temp directory, downloading source tarball from Amberdms...");


				// download file from Amberdms and store in temp directory
				if (!copy($amberdms_source[ $version ]["url"] . $amberdms_source[ $version ]["file"], $path_tmpdir ."/". $amberdms_source[ $version ]["file"]))
				{
					// download failed
					log_write("error", "execute", "Unable to download offical Amberdms source code from www.amberdms.com");
					return 0;
				}


				// copy from temp to working dir
				//
				// (we copy to temp first so that the tarball is cached across mutiple patch generations)
				//
				system("cp $path_tmpdir/". $amberdms_source[ $version ]["file"] ." ". $this->tmpdir ."/orig.tar.bz2");
			}


			// verify that file has been coppied to tmp dir
			if (!file_exists($this->tmpdir ."/orig.tar.bz2"))
			{
				log_write("error", "execute", "An unexpected problem occured whilst copying source tarball to temporary unpacking directory.");
				return 0;
			}





			/*
				Copy all source from running version to temp location
			*/

			log_write("debug", "execute", "Copying active source...");

			mkdir($this->tmpdir ."/new");

			$filelist = dir_list_contents(".");

			// run through file list and only copy desired files/dirs
			foreach ($filelist as $file)
			{
				// exclude unwanted directories or files, such as:
				// * repository dirs
				// * configuration files
				// * temporary editor files
				if (!strpos($file, "CVS") && !strpos($file, ".swp") && !strpos($file, "config-settings.php"))
				{
					if (is_dir($file))
					{
						mkdir($this->tmpdir ."/new/$file");
					}
					else
					{
						copy("$file", $this->tmpdir ."/new/$file");
					}
				}
			}



			/*
				Extract Amberdms Source
			*/

			log_write("debug", "execute", "Extracting Amberdms Source Code");

			// extract
			chdir($this->tmpdir);
			system("tar -xkjf ". $this->tmpdir ."/orig.tar.bz2");
			system("mv ". $this->tmpdir ."/amberdms-bs-$version ". $this->tmpdir ."/orig");

			// verify successful extract
			if (!file_exists($this->tmpdir ."/orig"))
			{
				log_write("error", "execute", "An unexpected error occured whilst attempting to unpack Amberdms source code");
				return 0;
			}

		

			/*
				Generate diff of source code
			*/
			
			log_write("debug", "execute", "Generating diff file...");

			chdir($this->tmpdir);
			exec("diff -Nuar orig/ new/ > ". $this->tmpdir ."/abspatch", $output, $return);

			if ($return == "0")
			{
				// no changes
				log_write("notification", "process", "No recent changes found, not generating patch.");
				return 0;
			}
			elseif ($return == "2")
			{
				// failure
				log_write("error", "process", "An error occured whilst trying to create a patch.");
				return 0;
			}
			else
			{
				// patch generated! :-)
				log_write("notification", "process", "New patch successfully generated!");
			}
		}


		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "diff_generate";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "source/diff_submit-process.php";
		$this->obj_form->method = "post";
		

		// patch contents
		$structure = NULL;
		$structure["fieldname"] 		= "patch";
		$structure["type"]			= "textarea";
		$structure["options"]["no_fieldname"]	= 1;
		$structure["options"]["width"]		= "1000";
		$structure["options"]["height"]		= "400";
		$structure["options"]["wrap"]		= "off";
		$this->obj_form->add_input($structure);


		// amberdms submit options	
		$structure = NULL;
		$structure["fieldname"] 		= "patch_submit_notes";
		$structure["type"]			= "message";
		$structure["defaultvalue"]		= "<p>If your changes are likely to be useful to others, please consider submitting this patch to Amberdms - if we accept it, your code will be added to the application and you will be credited for the development.</p>";
		$structure["options"]["req"]		= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 		= "patch_submit_legal";
		$structure["type"]			= "checkbox";
		$structure["options"]["req"]		= "yes";
		$structure["options"]["no_fieldname"]	= 1;
		$structure["options"]["label"]		= "<b>I would like to submit this source code to Amberdms and agree to assign copyright ownership to Amberdms Ltd (New Zealand)</b><br><br>";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 		= "patch_submit_contact";
		$structure["type"]			= "input";
		$structure["defaultvalue"]		= user_information("contact_email");
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 		= "patch_submit_credit";
		$structure["type"]			= "input";
		$structure["defaultvalue"]		= user_information("realname");
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 		= "patch_description";
		$structure["type"]			= "textarea";
		$structure["options"]["width"]		= "600";
		$structure["options"]["height"]		= "300";
		$structure["defaultvalue"]		= "Include information about what features this patch provides as well as any modifications you made to the SQL database to go along with this code.";
		$this->obj_form->add_input($structure);


		// submit section
		$structure = NULL;
		$structure["fieldname"] 		= "submit";
		$structure["type"]			= "submit";
		$structure["defaultvalue"]		= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		$this->obj_form->subforms["patch_contents"]	= array("patch");
		$this->obj_form->subforms["patch_submit"]	= array("patch_submit_notes", "patch_submit_legal", "patch_submit_contact", "patch_submit_credit", "patch_description");
		$this->obj_form->subforms["submit"]		= array("submit");

		if ($_SESSION["error"]["message"])
		{
			// load data from error return
			$this->obj_form->load_data();

			// fix patch data
			$this->obj_form->structure["patch"]["defaultvalue"]	= format_text_textarea($_SESSION["error"]["patch"]);
		}
		else
		{
			// upload patch data into form
			$this->obj_form->structure["patch"]["defaultvalue"]  = "";
			$this->obj_form->structure["patch"]["defaultvalue"] .= "Date:		". date("Y-m-d") ."\n";
			$this->obj_form->structure["patch"]["defaultvalue"] .= "App Version:	". $GLOBALS["config"]["app_version"] ."\n";
			$this->obj_form->structure["patch"]["defaultvalue"] .= "Details:	Patch of modifications made against the Amberdms Billing System by a third-party hosting provider.\n";
			$this->obj_form->structure["patch"]["defaultvalue"] .= "\n";
			$this->obj_form->structure["patch"]["defaultvalue"] .= format_text_textarea(file_get_contents($this->tmpdir ."/abspatch"));
		}


		/*
			Clean up patch data
		*/
		log_write("debug", "execute", "Deleting patch data...");

		if ($this->tmpdir)
		{
			chdir("/");
			system("rm -rf ". $this->tmpdir);
		}
	}


	function render_html()
	{
		if ($this->obj_form)
		{
			print "<h3>PATCH GENERATED</h3>";
			print "<p>A patch has been generated showing all the changes between the official Amberdms source code and the customised version running on this server.</p>";

			$this->obj_form->render_form();
		}
		else
		{
			// no patch was generated.
			print "<h3>NO PATCH GENERATED</h3>";
			print "<p>No changes available to generate a patch from.</p>";
		}
	}

}

?>
