var dropdown_array = [];

function obj_hide(obj)
{
	document.getElementById(obj).style.display = 'none';
}
function obj_show(obj)
{
	document.getElementById(obj).style.display = '';
}

function openPopup(url)
{
	popup = window.open(url, 'popup', 'height=700, width=800, left=10, top=10, resizable=yes, scrollbars=yes, toolbar=no, menubar=no, location=no, directories=no');
}

// Creates a filter to do case insensitive content checks
jQuery.expr[':'].icontains = function(a, i, m)
{ 
	return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0; 
};


$(document).ready(function()
{
	//create array of filtered dropdowns on the page to enable roll backs.
	$(".dropdown_filter").each(function()
	{
		filter_id = this.id;
		dropdown_name = filter_id.substring(1);
		dropdown_array[dropdown_name] = $("select[name='" + dropdown_name + "']").clone(true);
		
		//when user selects option from the drop down, display it in the filter text box
		$("select[name='" + dropdown_name + "']").live("change", function()
		{
			text = $("select[name='" + dropdown_name + "'] option:selected").text();
			$("#" + filter_id).val(text);
		});
	});

	//create timer so function is not fired until user has stopped typing
	//currently set to fire after 500ms pause, could be lowered
	$(".dropdown_filter").live("keyup", function()
	{
		var timer;
		filter_id = this.id;
		dropdown_name = filter_id.substring(1);
		filter_string = $(this).val();
		
		clearTimeout(timer);
		timer = setTimeout("filter_dropdown(dropdown_name, filter_string)", 500);
	});
	
	//prevent form submission when user uses filter input box
	$(".dropdown_filter").live("keypress", function(e)
	{
		if (e.keyCode == 13)
		{
			$(this).siblings("select").focus();
			return false;
		}
	});
	
	//clear input when focus is on filtering box
	$(".dropdown_filter").live("click select", function()
	{
		$(this).val("");
	})

});

//changes the dropdown to the stored version, removes options that do not match the filtered string, and disables the dropdown if there are no matches
function filter_dropdown(dropdown_name, filter_string)
{	
	original_dropdown = dropdown_array[dropdown_name];
	contents = $(original_dropdown).html();
	$("select[name='" + dropdown_name + "']").html($(contents)).removeAttr("disabled");
	
	new_dropdown = $("select[name='" + dropdown_name + "']");
	$("option:not(:icontains('" + filter_string + "'))", new_dropdown).remove();

	if($("select[name='" + dropdown_name + "'] option").size() == 0)
	{
		$("select[name='" + dropdown_name + "']").html("<option value=\"\">--no matches--</option>").attr("disabled", "disabled");
	}
}