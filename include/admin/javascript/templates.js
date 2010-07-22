$(document).ready(function()
{
	$(".change_template").click(function()
	{
		parent_table = $(this).parents("table.template_table");
		$(".available_templates_row").hide();
		$(".available_templates_row", parent_table).show();
		return false;
	})
	
	$("table.template_table .cancelbutton").click(function()
	{		
		$(".available_templates_row").hide();
	});

});