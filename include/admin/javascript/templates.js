$(document).ready(function()
{
	$(".change_template").click(function()
	{
		if (this.id == "change_ar_invoice_template")
		{
			$(".ar_invoices_templates").show();
		}
		return false;
	})
	
	$(".cancelbutton").click(function()
	{
		if (this.id == "cancelbuton_ar_invoices")
		{
			$(".ar_invoices_templates").hide();
		}
	});

});