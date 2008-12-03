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
		return 1;
	}

	function execute()
	{
		// nothing todo
	}

	function render_html()
	{
		// nothing todo
		print "test";
	}
}

?>
