/*
	include/javascript/services.js

	Functions to assist with services pages.
*/

$(document).ready(function()
{

	/*
	 *  Hide edit/delete links on load when in group mode - we want to define the links
	 *  on the server side, but then adjust them with javascript.
	 */

	$(".rate_prefix").each(function() {
		
		prefixes_array = $(this).text().split(", ");

		if (prefixes_array.length > 1)
		{
			// more than one prefix in the row, hide the edit/delete links
			$(this).parent().children(".table_links").children(".cdr_edit").hide();
			$(this).parent().children(".table_links").children(".cdr_delete").hide();
		}
		else
		{
			// only one prefix, no need to expand
			$(this).parent().children(".table_links").children(".cdr_expand").hide();
		}
	});


	/*
	 *  CDR Rate Table Expand
	 */
	$(".cdr_expand").live("click", function(event){
		event.preventDefault();
		var cell = $(this).parent().parent();
		cdr_expand_table(cell);
		return false;
	});
});


/*
	cdr_expand_table

	Clones the selected table row and adjusts the ID of the link
*/
function cdr_expand_table(previous_row)
{
	// read in the array of prefixes from the previous row - we select the text string and drop the
	// leading space
	prefixes		= $(previous_row).children(".rate_prefix").text();

	console.log("Obtained Prefix List:");
	console.log(prefixes);

	// for each prefix, we need to create a new row and assign just one prefix to it
	prefixes_array		= prefixes.split(", ");
	prefixes_array		= prefixes_array.sort().reverse();


	// loop through each prefix in the array, adding a table row for each.
	for (i=0;i<prefixes_array.length;i++)
	{
		prefixes_array[i].trim();

		console.log("Processing Prefix: ", prefixes_array[i]);

		var new_row;

		// clone row
		new_row	= $(previous_row).clone().insertAfter(previous_row);

		// replace prefixes with a single value
		$(new_row).children(".rate_prefix").text(prefixes_array[i]);

		// hide expand button
		$(new_row).children(".cdr_expand").hide();

		// adjust edit/delete button hyperlinks
		button_url = $(new_row).children(".table_links").html().toString();
		button_url = button_url.replace(/id_rate=[0-9]*/g, "prefix=" + prefixes_array[i]); 
		$(new_row).children(".table_links").html(button_url);

		// hide/show buttons
		$(new_row).children(".table_links").children(".cdr_expand").hide();
		$(new_row).children(".table_links").children(".cdr_edit").show();
		$(new_row).children(".table_links").children(".cdr_delete").show();

	}

	// hide parent expand button
	$(previous_row).children(".table_links").children(".cdr_expand").hide();


	return false;
}


