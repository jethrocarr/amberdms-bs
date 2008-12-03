<?php
//
// message.php
//
// used as a "filler" page. When given this page argument, index.php will not display any pages, but will instead display error and/or notification messages only.
//

class page_output
{
	function check_permissions()
	{
		// allow all users (logged and logged out)
		return 1;
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}

	function execute()
	{
		// nothing todo
		return 1;
	}

	function render_html()
	{
		// nothing todo
		return 1;
	}
}

?>
