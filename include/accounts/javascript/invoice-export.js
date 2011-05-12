/*
 * 	include/accounts/javascript/invoice-export.js
 * 
 * 	Javascript functions for the invoice-export.php page
 */

$(document).ready(function()
{
	/*
	 * 	When a submit button is clicked on, refresh the page
	 * 	Waits for one seconds to ensure processing has finished
	 * 	Ensures 'sent' status and errors are displayed as soon as invoice is downloaded or emailed
	 */
	$("input[type='submit']").click(function()
	{
		setTimeout("location.reload(true)", 8000);
	});
});
