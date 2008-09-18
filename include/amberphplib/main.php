<?php
/*
	AMBERPHPLIB

	PHP functions + classed developed by Amberdms for use in various products.

	All code is Licensed under the GNU GPL version 2. If you wish to use this
	code in a propietary/commercial product, please contact sales@amberdms.com.
*/

// Important that we include language first, since other functions
// require it.
include("inc_language.php");

// DB SQL processing and execution
include("inc_sql.php");

// User + Security Functions
include("inc_user.php");
include("inc_security.php");

// Error Handling
include("inc_errors.php");

// Misc Functions
include("inc_misc.php");

// Functions/classes for data entry and processing
include("inc_forms.php");
include("inc_tables.php");

// Journal System
require("inc_journal.php");


?>
