<?php
/*
	AMBERPHPLIB

	PHP functions + classed developed by Amberdms for use in various products.

	All code is Licensed under the GNU GPL version 2. If you wish to use this
	code in a propietary/commercial product, please contact sales@amberdms.com.
*/

// Important that we include language first, since other functions
// require it.
include("language.php");

// DB SQL processing and execution
include("sql.php");

// User + Security Functions
include("user.php");
include("security.php");

// Error Handling
include("errors.php");

// Misc Functions
include("misc.php");

// Functions/classes for data entry and processing
include("forms.php");
include("tables.php");


?>
