/*
	include/accounts/javascript/import.js

	Provides key UI logic for handling the Javascript import page, including
	the CSV handling logic and the row assignement logic.
*/

$(document).ready(function()
{
	$('div.input_structure input.selected_structure').change( function()
	{
		$('div.input_structure .custom_structure').css('display', 'none');
		this_input_structure = $(this).parents('div.input_structure'); 
		$('.custom_structure', this_input_structure).css('display', 'block');
		if($(this_input_structure).hasClass('other_structure'))
		{
			// Grab and detach the custom structure form fields and container.
			custom_structure = $("div#custom_structure").detach();
			
			// Wipe the custom structure forms
			$("input[name=structure_id]", custom_structure).val('');
			$("input[name=name]", custom_structure).val('');
			$("input[name=description]", custom_structure).val('');

			$("option[name^=column]", custom_structure).removeAttr('selected');
			$("option[name^=column][value=]", custom_structure).attr('selected', 'selected');

			// append and display the custom structure form fields.
			$(this_input_structure).append(custom_structure);
			$('.custom_structure', this_input_structure).css('display', 'block');
		}
		
		
	});
	
	
	
	$("div.input_structure a.edit_item_link").click( function() {
		this_input_structure = $(this).parents('div.input_structure');
		
		//get our structure ID.
		structure_id = $("input.selected_structure", this_input_structure).val(); 
		
		$.getJSON("accounts/ajax/modify_input_structure.php", {action: "get-data", id: structure_id}, 
				function(json)
				{
					// Grab and detach the custom struycture form fields and container.
					custom_structure = $("div#custom_structure").detach();
					
					// Stick the form values into the structure forms.
					$("input[name=structure_id]", custom_structure).val(json['id']);
					$("input[name=name]", custom_structure).val(json['name']);
					$("input[name=description]", custom_structure).val(json['description']);
					
					
					// wipe all the selected options
					$("option", custom_structure).removeAttr('selected');
					
					$('div.column_format', custom_structure).css('display', 'none');
					$('div.column_format select', custom_structure).attr('disabled', 'disabled');
					
					// loop though the items, javascript substitute for a foreach loop
					for(var i in json['items'])
					{
						// Stick the target row in a variable called row
						row = json['items'][i];

						// grab the target form element and store it so we don't keep having to grab it. 
						target_select = $("select[name=column"+row['field_src']+"]", custom_structure);
						// get the format container too
						column_format = $(target_select).siblings('div.column_format');

						// deselect all options, then select the correct one.
						$("option[value="+row['field_dest']+"]", target_select).attr('selected', 'selected');

						// Enable, select the right option of and display the date format, if applicable.
						if(row['field_dest'] == 'date')
						{
							$('select',column_format).removeAttr('disabled');
							$("select option[value="+row['data_format']+"]",column_format).attr('selected', 'selected');
							$(column_format).css('display', 'block');
						}
					}
					// use the structure ID to get the container of the item we are working on
					this_input_structure = $("div.structure_"+json['id']);
					
					// uncheck all the radio buttons
					$("div.input_structure input.selected_structure").removeAttr("checked");
					// check the one we are working on
					$("input.selected_structure", this_input_structure).attr("checked", "checked");
					
					// append and display the custom structure form fields.
					$(this_input_structure).append(custom_structure);
					$('.custom_structure', this_input_structure).css('display', 'block');
					
				}
		);
		// cancel the click action, we are done here.
		return false;
	});
	

	$("div.input_structure a.delete_item_link").click( function() {
		this_input_structure = $(this).parents('div.input_structure');
		
		//get our structure ID.
		structure_id = $("input.selected_structure", this_input_structure).val(); 
		
		if(window.confirm("Are you sure you want to delete this item?")) 
		{
			$.getJSON("accounts/ajax/modify_input_structure.php", {action: "delete", id: structure_id}, 
					function(json)
					{
						if(json['success-state'] == true)
						{
							// use the structure ID to get the container of the item we are working on
							this_input_structure = $("div.structure_"+json['id']);
							$(this_input_structure).fadeOut();
						}
					}
			);
		}
	});

	
	$("div.custom_structure select.column_selection").change( function() {
		column_format = $(this).siblings('div.column_format');
		switch($(this).val())
		{
			case 'date':
				$(column_format).css('display', 'block');
				$('select',column_format).removeAttr('disabled');
			break;
			
			default:
				$(column_format).css('display', 'none');
				$('select',column_format).attr('disabled', 'disabled');
			break;
			
		}
	});
	
	
	
	
	
	// functions for the bankstatment-assign page
	//on remove/add click
	$(".include").click(function()
	{
		toggleIncludeTransaction($(this));
	});  
	
	//on change of assign dropdown
	$(".assign").children().change(function()
	{
		toggleSubMenus($(this));
	});
	
	//on change of sub menu drop downs
	$("div[class*='toggle']").children().change(function()
	{
		checkSubmenusComplete($(this));
	});

	$("td.include input[name^=transaction]").each( function(index, element) {
		//$(elem).parents("td").css('border', '1px solid red');
		parent_element = $(element).parents("td.include");

		parent_row = $(parent_element).parents("tr.transaction_row");
		if($(element).val() == 'false') {
			$("td", parent_row).css("font-style", "italic").fadeTo("fast", 0.5);
			$(".dropdown select", parent_row).attr("disabled", "disabled");
			
			$('img', parent_element).attr("src", "images/icons/plus.gif");
			$('input', parent_element).attr("value", "false");
			
			$(parent_element).removeClass("remove").addClass("add");
			$(".done", parent_row).children().fadeOut("fast");
			$(".dropdown", parent_row).children("div:not('.assign')").fadeOut("fast");
		}
		else 
		{
			if ($(".dropdown .assign", parent_row).children().val() != "")
			{
				toggleSubMenus($(".dropdown .assign", parent_row).children());
			}
		}
	});
	

	
});






// Toggle italics, opacity, image, tick mark, and drop down menus when a transaction is added or removed
function toggleIncludeTransaction(elem)
{
	elem_parent = $(elem).parents("tr.transaction_row");
	if($(elem).hasClass("remove"))
	{

		$("td", elem_parent).css("font-style", "italic").fadeTo("fast", 0.5);
		$(".dropdown select", elem_parent).attr("disabled", "disabled");
		
		$('img', elem).attr("src", "images/icons/plus.gif");
		$('input', elem).attr("value", "false");
		
		$(elem).removeClass("remove").addClass("add");
		$(".done", elem_parent).children().fadeOut("fast");
		$(".dropdown", elem_parent).children("div:not('.assign')").fadeOut("fast");
	}
	else
	{
		$("td", elem_parent).css("font-style", "normal").fadeTo("fast", 1);
		$(".dropdown select", elem_parent).removeAttr("disabled");
		
		$('img', elem).attr("src", "images/icons/minus.gif");
		$('input', elem).attr("value", "true");
		
		$(elem).removeClass("add").addClass("remove");
		if ($(".dropdown .assign", elem_parent).children().val() != "")
		{
			toggleSubMenus($(".dropdown .assign", elem_parent).children());
		}
	}
}


function toggleSubMenus(elem)
{
	assigndiv = $(elem).parent();
	
	if ($(elem).val() == "")
	{
		$(assigndiv).siblings().fadeOut("fast");
		$(assigndiv).parent().siblings(".done").children().fadeOut("fast");
	}
	else
	{	
		trans_type = $(elem).val();
		submenu_div = $(assigndiv).siblings("div[class*='" + trans_type + "']");
		
		//show and hide divs
		$(submenu_div).fadeIn("fast");
		$(assigndiv).siblings("div:not([class*='" + trans_type + "'])").fadeOut("fast");
		//check completeness of submenus
		done = 1;
		$(submenu_div).children("select").each(function()
		{
			if ($(this).val() == "")
			{
				done = 0;
			}
		});
		
		if (done)
		{
			$(assigndiv).parent().siblings(".done").children().fadeIn("fast");
		}
		else
		{
			$(assigndiv).parent().siblings(".done").children().fadeOut("fast");
		}
	}
}

function checkSubmenusComplete(elem)
{
	done = 1;
	if($(elem).val() != "")
	{
		$(elem).siblings("select").each(function()
		{
			if($(this).val() == "")
			{
				done = 0;
			}
		});
	}
	else
	{
		done = 0;
	}
	
	if (done)
	{
		$(elem).parent().parent().siblings(".done").children().fadeIn("fast");
	}
	else
	{
		$(elem).parent().parent().siblings(".done").children().fadeOut("fast");
	}
}
