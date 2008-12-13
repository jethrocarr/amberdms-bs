<?php
/*
	include/accounts/inc_quotes.php

	Contains various help, wrapper and useful functions for working with quotes in the database.
*/


/*
	FUNCTIONS
*/




/*
	quotes_calc_duedate($date)

	This function takes the supplied date in YYYY-MM-DD format, and
	adds the number of days for the default payment term in the DB
	and returns a new due date value - this is suitable for the default
	valid date on quotes

	Returns the data in YYYY-MM-DD format.
*/
function quotes_calc_duedate($date)
{
	log_debug("inc_quotes_details", "Executing quotes_calc_duedate($date)");
	
	// get the terms
	$terms = sql_get_singlevalue("SELECT value FROM config WHERE name='ACCOUNTS_TERMS_DAYS'");

	// break up the date, and reconfigure
	$date_array	= split("-", $date);
	$timestamp	= mktime(0, 0, 0, $date_array[1], ($date_array[2] + $terms), $date_array[0]);

	// generate the date
	return date("Y-m-d", $timestamp);
}



/*
	quotes_render_summarybox($id)

	Displays a summary box showing the status of the quote (paid or unpaid) and information
	on the total of the quote and total amount of payments.

	Values
	id	id of the quote

	Return Codes
	0	failure
	1	sucess
*/

function quotes_render_summarybox($id)
{
	log_debug("inc_quotes", "quotes_render_summarybox($id)");

	// fetch quote information
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT code_quote, amount_total, date_validtill, date_sent, sentmethod FROM account_quotes WHERE id='$id' LIMIT 1";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$sql_obj->fetch_array();

		if ($sql_obj->data[0]["amount_total"] == 0)
		{
			print "<table width=\"100%\" class=\"table_highlight_important\">";
			print "<tr>";
				print "<td>";
				print "<b>Quote ". $sql_obj->data[0]["code_quote"] ." has no items on it</b>";
				print "<p>This quote needs to have some items added to it using the links in the nav menu above.</p>";
				print "</td>";
			print "</tr>";
			print "</table>";
		}
		else
		{
			if (time_date_to_timestamp($sql_obj->data[0]["date_validtill"]) <= mktime())
			{
				print "<table width=\"100%\" class=\"table_highlight_important\">";
				print "<tr>";
					print "<td>";
					print "<p><b>Quote ". $sql_obj->data[0]["code_quote"] ." has now expired and is no longer valid.</b></p>";
					print "</td>";
				print "</tr>";
				print "</table>";
			}
			else
			{
				print "<table width=\"100%\" class=\"table_highlight_important\">";
				print "<tr>";
					print "<td>";
					print "<b>Quote ". $sql_obj->data[0]["code_quote"] ." is currently valid.</b>";

					print "<table cellpadding=\"4\">";
					
					print "<tr>";
						print "<td>Quote Total:</td>";
						print "<td>$". $sql_obj->data[0]["amount_total"] ."</td>";
					print "</tr>";
					
					print "<tr>";
						print "<td>Valid Until:</td>";
						print "<td>". $sql_obj->data[0]["date_validtill"] ."</td>";
					print "</tr>";
			
					print "<tr>";
						print "<td>Date Sent:</td>";

						if ($sql_obj->data[0]["sentmethod"] == "")
						{
							print "<td><i>Has not been sent to customer</i></td>";
						}
						else
						{
							print "<td>". $sql_obj->data[0]["date_sent"] ." (". $sql_obj->data[0]["sentmethod"] .")</td>";
						}
					print "</tr>";

					
					print "</tr></table>";
					
					print "</td>";
				print "</tr>";
				print "</table>";
	
			}
		}

		print "<br>";
	}
}



?>
