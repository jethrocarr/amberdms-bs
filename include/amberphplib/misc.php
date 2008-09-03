<?php
//
// misc.php
// 
// Various one-off functions
//
// HELP FUNCTIONS
//
// helplink( id )
//	returns an html string, including a help icon, with a hyperlink to the help page specified by id.
//

/* HELP FUNCTIONS */


function helplink($id)
{
	return "<a href=\"help/viewer.php?id=$id\" target=\"new\" title=\"Click here for a popup help box\"><img src=\"images/icons/help.gif\" alt=\"?\" border=\"0\"></a>";
}



?>
