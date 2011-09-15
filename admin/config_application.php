<?php
/*
	admin/config_application.php
	
	access: admin users only

	Provides options to configure the general application settings.
*/

class page_output
{
	var $obj_form;


	function check_permissions()
	{
		return user_permissions_get("admin");
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
		$this->obj_form->formname = "config_application";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "admin/config_application-process.php";
		$this->obj_form->method = "post";


		// default codes
		$structure = NULL;
		$structure["fieldname"]				= "ACCOUNTS_AP_INVOICENUM";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]				= "ACCOUNTS_AR_INVOICENUM";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "ACCOUNTS_GL_TRANSNUM";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "ACCOUNTS_QUOTES_NUM";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "ACCOUNTS_CREDIT_NUM";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);


		$structure = NULL;
		$structure["fieldname"]				= "CODE_ACCOUNT";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "CODE_CUSTOMER";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]				= "CODE_PRODUCT";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "CODE_PROJECT";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "CODE_VENDOR";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "CODE_STAFF";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);



		// invoicing options
		$structure = NULL;
		$structure["fieldname"]				= "ACCOUNTS_SERVICES_ADVANCEBILLING";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$structure["options"]["width"]			= "50";
		$structure["options"]["label"]			= " days";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "ACCOUNTS_SERVICES_DATESHIFT";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$structure["options"]["width"]			= "50";
		$structure["options"]["label"]			= " ". lang_trans("help_accounts_services_dateshift");
		$this->obj_form->add_input($structure);


		$structure = NULL;
		$structure["fieldname"]				= "ACCOUNTS_TERMS_DAYS";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "ACCOUNTS_AUTOPAY";
		$structure["type"]				= "checkbox";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$structure["options"]["label"]			= " Check to have invoices automatically paid where there is credit or reoccuring billing details.";
		$this->obj_form->add_input($structure);



		// email options
		$structure = NULL;
		$structure["fieldname"]				= "ACCOUNTS_EMAIL_ADDRESS";
		$structure["type"]				= "input";
		$structure["options"]["label"]			= " Internal email address to send billing system related emails to.";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "ACCOUNTS_INVOICE_AUTOEMAIL";
		$structure["type"]				= "checkbox";
		$structure["options"]["label"]			= "Tick to have service and order invoices automatically emailed to customers when created, from address will be COMPANY_EMAIL_ADDRESS";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "ACCOUNTS_INVOICE_BATCHREPORT";
		$structure["type"]				= "checkbox";
		$structure["options"]["label"]			= "Tick to have an invoice batch report sent to ACCOUNTS_EMAIL_ADDRESS when invoices are automatically generated.";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "ACCOUNTS_EMAIL_AUTOBCC";
		$structure["type"]				= "checkbox";
		$structure["options"]["label"]			= "Always BCC outgoing invoice emails to ACCOUNTS_EMAIL_ADDRESS";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);


		// service email options
		$structure = NULL;
		$structure["fieldname"]				= "SERVICES_USAGEALERTS_ENABLE";
		$structure["type"]				= "checkbox";
		$structure["options"]["label"]			= "Tick to have service usage alerts delivered to customers (where customers/services are enabled for it).";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);


		// orders options
		$structure = NULL;
		$structure["fieldname"]				= "ORDERS_BILL_ONSERVICE";
		$structure["type"]				= "checkbox";
		$structure["options"]["label"]			= "Automatically bill customer orders when the next service bill is generated.";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "ORDERS_BILL_ENDOFMONTH";
		$structure["type"]				= "checkbox";
		$structure["options"]["label"]			= "Automatically bill customer orders at the end of the calender month.";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);



		// timesheet options
		$structure = NULL;
		$structure["fieldname"]				= "TIMESHEET_BOOKTOFUTURE";
		$structure["type"]				= "checkbox";
		$structure["options"]["label"]			= "Allow users to book time to dates in the future";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);
	
	
		

		// audit locking
		$structure = NULL;
		$structure["fieldname"]				= "ACCOUNTS_INVOICE_LOCK";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]				= "ACCOUNTS_GL_LOCK";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);
			
		$structure = NULL;
		$structure["fieldname"]				= "JOURNAL_LOCK";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "TIMESHEET_LOCK";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);




		// misc	
		$structure = NULL;
		$structure["fieldname"]				= "UPLOAD_MAXBYTES";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$structure["options"]["label"]			= " Bytes. Server maximum is ". ini_get('upload_max_filesize') .", to increase server limit, you must edit php.ini";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]				= "API_URL";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$structure["options"]["label"]			= " This URL will be used in namespace and soap address URLS in the WSDL files.";
		$this->obj_form->add_input($structure);
	

		// contributions
		$structure = NULL;
		$structure["fieldname"]				= "PHONE_HOME";
		$structure["type"]				= "checkbox";
		$structure["options"]["label"]			= "Phone home to Amberdms with application, OS and PHP version so we can better improve this software. (all information is anonymous and private)";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);


		// security options
		$structure = NULL;
		$structure["fieldname"]				= "SESSION_TIMEOUT";
		$structure["type"]				= "input";
		$structure["options"]["label"]			= " seconds idle before logging user out";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$structure["defaultvalue"]			= "7200";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "BLACKLIST_ENABLE";
		$structure["type"]				= "checkbox";
		$structure["options"]["label"]			= "Enable to prevent brute-force login attempts";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "BLACKLIST_LIMIT";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);


	
		// dangerous options
		if ($GLOBALS["config"]["dangerous_conf_options"] == "enabled")
		{
			$structure = NULL;
			$structure["fieldname"]				= "EMAIL_ENABLE";
			$structure["type"]				= "checkbox";
			$structure["options"]["label"]			= "Enable or disable the ability to send emails. If you don't trust users not to try using the system to spam people (eg: if this is a demo system) then it is highly recommended to disable this option.";
			$structure["options"]["no_translate_fieldname"]	= "yes";
			$this->obj_form->add_input($structure);
	
			$structure = NULL;
			$structure["fieldname"]				= "DATA_STORAGE_METHOD";
			$structure["type"]				= "radio";
			$structure["values"]				= array("database", "filesystem");
			$structure["options"]["no_translate_fieldname"]	= "yes";
			$this->obj_form->add_input($structure);
	
			$structure = NULL;
			$structure["fieldname"]				= "DATA_STORAGE_LOCATION";
			$structure["type"]				= "input";
			$structure["options"]["no_translate_fieldname"]	= "yes";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]				= "PATH_TMPDIR";
			$structure["type"]				= "input";
			$structure["options"]["no_translate_fieldname"]	= "yes";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]				= "APP_PDFLATEX";
			$structure["type"]				= "input";
			$structure["options"]["no_translate_fieldname"]	= "yes";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]				= "APP_WKHTMLTOPDF";
			$structure["type"]				= "input";
			$structure["options"]["no_translate_fieldname"]	= "yes";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]				= "APP_MYSQL_DUMP";
			$structure["type"]				= "input";
			$structure["options"]["no_translate_fieldname"]	= "yes";
			$this->obj_form->add_input($structure);
		}

	

		// submit section
		$structure = NULL;
		$structure["fieldname"]			= "submit";
		$structure["type"]			= "submit";
		$structure["defaultvalue"]		= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		$this->obj_form->subforms["config_defcodes"]		= array("ACCOUNTS_AP_INVOICENUM", "ACCOUNTS_AR_INVOICENUM", "ACCOUNTS_GL_TRANSNUM", "ACCOUNTS_QUOTES_NUM", "ACCOUNTS_CREDIT_NUM", "CODE_ACCOUNT", "CODE_CUSTOMER", "CODE_VENDOR", "CODE_PRODUCT", "CODE_PROJECT", "CODE_STAFF");
		$this->obj_form->subforms["config_accounts"]		= array("ACCOUNTS_SERVICES_ADVANCEBILLING", "ACCOUNTS_SERVICES_DATESHIFT", "ACCOUNTS_TERMS_DAYS", "ACCOUNTS_AUTOPAY");
		$this->obj_form->subforms["config_accounts_email"]	= array("ACCOUNTS_EMAIL_ADDRESS", "ACCOUNTS_INVOICE_AUTOEMAIL", "ACCOUNTS_EMAIL_AUTOBCC", "ACCOUNTS_INVOICE_BATCHREPORT");
		$this->obj_form->subforms["config_services_email"]	= array("SERVICES_USAGEALERTS_ENABLE");
		$this->obj_form->subforms["config_orders"]		= array("ORDERS_BILL_ONSERVICE", "ORDERS_BILL_ENDOFMONTH");
		$this->obj_form->subforms["config_timesheet"]		= array("TIMESHEET_BOOKTOFUTURE");
		$this->obj_form->subforms["config_auditlocking"]	= array("ACCOUNTS_INVOICE_LOCK", "ACCOUNTS_GL_LOCK", "JOURNAL_LOCK", "TIMESHEET_LOCK");
		$this->obj_form->subforms["config_contributions"]	= array("PHONE_HOME");
		$this->obj_form->subforms["config_security"]		= array("SESSION_TIMEOUT", "BLACKLIST_ENABLE", "BLACKLIST_LIMIT");
		$this->obj_form->subforms["config_misc"]		= array("UPLOAD_MAXBYTES", "API_URL");

		if ($GLOBALS["config"]["dangerous_conf_options"] == "enabled")
		{
			$this->obj_form->subforms["config_dangerous"]	= array("PATH_TMPDIR", "APP_PDFLATEX", "APP_WKHTMLTOPDF", "APP_MYSQL_DUMP", "EMAIL_ENABLE", "DATA_STORAGE_LOCATION", "DATA_STORAGE_METHOD");
		}
		
		$this->obj_form->subforms["submit"]			= array("submit");

		if (error_check())
		{
			// load error datas
			$this->obj_form->load_data_error();
		}
		else
		{
			// fetch all the values from the database
			$sql_config_obj		= New sql_query;
			$sql_config_obj->string	= "SELECT name, value FROM config ORDER BY name";
			$sql_config_obj->execute();
			$sql_config_obj->fetch_array();

			foreach ($sql_config_obj->data as $data_config)
			{
				$this->obj_form->structure[ $data_config["name"] ]["defaultvalue"] = $data_config["value"];
			}

			unset($sql_config_obj);
		}
	}



	function render_html()
	{
		// Title + Summary
		print "<h3>CONFIGURATION APPLICATION</h3><br>";
		print "<p>This page allows you to adjust the application configuration. Make sure you understand any options before adjusting them - refer to the product manual for help information.</p>";
	
		// display the form
		$this->obj_form->render_form();
	}
	
}

?>
