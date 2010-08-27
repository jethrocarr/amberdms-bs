/*
 * 	invoice-bulk-payments_ap.js
 * 	
 * 	Javascript functions for accounts/ap/invoice-bulk-payments.php
 */

$(document).ready(function()
{
	$("input[id^='pay_invoice_']").change(function()
	{
		id = this.id.substring(12);
		
		//populate default payment amount when "pay" is ticked
		if ($("input[name='checked_status_invoice_" + id + "']").val() == "false")
		{
			get_amount_due(id);
			$("input[name='checked_status_invoice_" + id + "']").val("true");
		}
		
		//remove text from input when "pay" is deselected
		else
		{
			$("input[name='amount_invoice_" + id + "']").val("");
			$("input[name='checked_status_invoice_" + id + "']").val("false");
		}
	});
	
	$("input[name^='amount_invoice_']").change(function()
	{
		id = $(this).attr("name").substring(15);
		
		//tick "pay" box if amount is entered into input
		if ($(this).val().length > 0)
		{
			$("input[name='checked_status_invoice_" + id + "']").val("true");
			$("#pay_invoice_" + id).attr("checked", true);
		}
		
		//remove "pay" tick if amount is deleted from input
		else
		{
			$("input[name='checked_status_invoice_" + id + "']").val("false");
			$("#pay_invoice_" + id).attr("checked", false);
		}
	});
});

//calculate and populate amount owed
function get_amount_due(id)
{
	$.get("accounts/ajax/get_amount_due_ap.php", {id: id}, function(amount)
	{
		$("input[name='amount_invoice_" + id + "']").val(amount);
	});
}