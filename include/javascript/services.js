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


	/*
	 * Traffic Cap UI Handling
	 *
	 * 1. Enable/disable entire rows depending on the check status of active.
	 * 2. Depending on the mode, we need to enable/disable the units included and price fields, as they only apply to capped traffic.
	 * 3. We need to load the unit type from the selected value and render inline with the units included field
	 */

	// disable any rows on load that are unselected
	$("input[name*='_active']").not(":checked").each(function()
	{
		console.log("Disabling inactive traffic cap rows");

		$(this).parent().siblings().children("select[name*='_mode']").attr("disabled", "disabled");
		$(this).parent().siblings().children("input[name*='_units_price']").attr("disabled", "disabled");
		$(this).parent().siblings().children("input[name*='_units_included']").attr("disabled", "disabled");
	});


	$("input[name*='_active']").live("change", function()
	{
		if ($(this).attr("checked"))
		{
			console.log("Enabling traffic cap row");

			$(this).parent().siblings().children("select[name*='_mode']").removeAttr("disabled");
			
			if ($(this).parent().siblings().children("select[name*='_mode']").val() == "capped")
			{
				$(this).parent().siblings().children("input[name*='_units_price']").removeAttr("disabled");
				$(this).parent().siblings().children("input[name*='_units_included']").removeAttr("disabled");
			}
		}
		else
		{
			console.log("Disabling traffic cap row");

			$(this).parent().siblings().children("select[name*='_mode']").attr("disabled", "disabled");
			$(this).parent().siblings().children("input[name*='_units_price']").attr("disabled", "disabled");
			$(this).parent().siblings().children("input[name*='_units_included']").attr("disabled", "disabled");
		}
	});



	// handle unlimited vs capped services UI
	$("select[name*='_mode']").each(function()
	{
		console.log("Disabling any unlimited cap fields on page load");

		if ($(this).val() == "unlimited")
		{
			$(this).parent().siblings().children("input[name*='_units_price']").attr("disabled", "disabled");
			$(this).parent().siblings().children("input[name*='_units_included']").attr("disabled", "disabled");
		}
	});

	$("select[name*='_mode']").live("change", function()
	{
		console.log("Traffic cap mode changes, enabling/disabling UI fields");

		if ($(this).val() == "unlimited")
		{
			$(this).parent().siblings().children("input[name*='_units_price']").attr("disabled", "disabled");
			$(this).parent().siblings().children("input[name*='_units_included']").attr("disabled", "disabled");
		}
		else
		{
			$(this).parent().siblings().children("input[name*='_units_price']").removeAttr("disabled");
			$(this).parent().siblings().children("input[name*='_units_included']").removeAttr("disabled");
		}
	});


	// fetch units for UI
	$("input[name^='units']:checked").each(function()
	{
		traffic_cap_units_update($(this));
	});
	
	$("input[name^='units']:checked").live("change",function()
	{
		traffic_cap_units_update($(this));
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



/*
	traffic_cap_units_update

	Updates the displayed units for traffic caps, based on selected values by user.
*/
function traffic_cap_units_update(radio_option)
{
	console.log("Executing traffic_cap_units_update()");

	unit_value = $(radio_option).val();
	unit_label = $("label[for^='units_" + unit_value + "']").text();

	pattern	= /^([A-Z]*)\s/;
	matches	= unit_label.match(pattern);

	if (matches)
	{
		unit_label = matches[1];
	}

	console.log("Unit value is: " + unit_value + ", label is " + unit_label + ", updating page labels");

	$("label[for*='units_included']").text(" " + unit_label);
	$("label[for*='units_price']").text(" per " + unit_label + " additional usage.");


	return false;
}

