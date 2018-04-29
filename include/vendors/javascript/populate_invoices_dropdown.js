/*
 * populate_invoices_dropdown.js
 * 
 * Functions to enable dynamic invoice selection on pages using vendor-invoice selection combinations.
 */

$(document).ready(function()
{
	
	// load invoice list on page load
	vendorid = $("select[name='vendorid']").val();
	populate_invoices_dropdown(vendorid);

	// when the customer id is selected, change the invoice id
	$("select[name='vendorid']").change(function()
	{
		vendorid = $(this).val();
		populate_invoices_dropdown(vendorid);
	});
});


/*
 * Ajax call to load the invoiceid dropdown menu with invoices matching the selected customer
 */
function populate_invoices_dropdown(vendorid)
{
	if (vendorid == "")
	{
		$("select[name='invoiceid']").html("<option value=\"\"> -- select vendor first -- </option>").attr("disabled", "disabled");
	}
	else
	{
		// fetch invoice ID from current options
		invoiceid = $("select[name='invoiceid']").val();

		// prepare variables to send to ajax page
		if (invoiceid)
		{
			selected = invoiceid;
		}
		else
		{
			selected = "";
		}
		
		$.get("vendors/ajax/populate_invoices_dropdown.php", {id_vendor: vendorid, id_selected: invoiceid}, 
			function(text)
			{
				//insert new options and enable dropdown
				$("select[name='invoiceid']").html(text).removeAttr("disabled");
				
				//select first in list if an option is not already selected
			//	if (!$("select[name='invoiceid'] option.selected").length)
			//	{
			//		$("select[name='invoiceid'] option[value='1']").attr("selected", "selected");
				//}
				
				//if no options exist, disable dropdown
				if ($("select[name='invoiceid']").val() == "")
				{
					$("select[name='invoiceid']").attr("disabled", "disabled");
					$("#_invoiceid").attr("disabled", "disabled").val("");
				}
				else
				{
					$("#_invoiceid").removeAttr("disabled");
				}
				
				//add dropdown to the dropdown array to enable searching
				dropdown_array["invoiceid"] = $("select[name='invoiceid']").clone(true);
			}
		);		
	}
}
