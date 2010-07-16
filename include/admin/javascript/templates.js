$(document).ready(function()
{
	$(".change_template").click(function()
	{
		parent_table = $(this).parents("table.template_table");
		$(".available_templates_row").hide();
		$(".available_templates_row", parent_table).show();
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