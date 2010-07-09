/*
	include/attributes/attributes.js

	Provides javascript logic for expanding and reducing the webforms.
*/

var num_values;

$(document).ready(function()
{
	/*
		When any element in the last row is changed (therefore, having data put into it), call a function to create a new row
	*/
	num_values = $("input[name='num_values']").val();
	
	$("select[name^='attribute_" + (num_values-1) + "']").change(add_attribute_row);
	$("input[name^='attribute_" + (num_values-1) + "']").change(add_attribute_row);
	$("textarea[name^='attribute_" + (num_values-1) + "']").change(add_attribute_row);

	/*
	 * 	Attach delete function to mouse click on delete link
	 */
	$(".delete_undo").live("click", function(){
		var cell = $(this).parent();
		delete_undo_row(cell);
		return false;
	});
	
	$(".delete_undo").live("select", function(){
		var cell = $(this).parent();
		delete_undo_row(cell);
		return false;
	});

});



/*
	add_attribute_row
*/
function add_attribute_row()
{
	previous_row		= $("input[name='attribute_" + (num_values-1) + "_key']").parent().parent();
	new_row			= $(previous_row).clone().insertAfter(previous_row);

	$(new_row).children().children("input[name='attribute_" + (num_values-1) + "_key']").removeAttr("name").attr("name", "attribute_" + num_values + "_key").val("");
	$(new_row).children().children("input[name='attribute_" + (num_values-1) + "_value']").removeAttr("name").attr("name", "attribute_" + num_values + "_value").val("");
	$(new_row).children().children("input[name='attribute_" + (num_values-1) + "_delete_undo']").removeAttr("name").attr("name", "attribute_" + num_values + "_delete_undo").val("false");
	
	$(new_row).children().children("input[name='attribute_" + num_values + "_key']").attr("id", "attribute_" + num_values + "_key");
	
	//remove function calls from previous row
	$("select[name^='attribute_" + (num_values-1) + "']").unbind("change");
	$("input[name^='attribute_" + (num_values-1) + "']").unbind("change", add_attribute_row);
	$("textarea[name^='attribute_" + (num_values-1) + "']").unbind("change");
	
	//add one to num_tran
	num_values++;
	$("input[name='num_values']").val(num_values);
	
	//add function calls to new row
	$("select[name^='attribute_" + (num_values-1) + "']").change(add_attribute_row);
	$("input[name^='attribute_" + (num_values-1) + "']").change(add_attribute_row);
	$("textarea[name^='attribute_" + (num_values-1) + "']").change(add_attribute_row);

	// set values for javascript autocomplete fields.
//	$("#attribute_" + (num_values-1) + "_key").autocomplete({
//		source: autocomplete_attribute_0_key
//	});


}



/*
 * 	delete_row
 * 
 * 	grey out row and set hidden delete variable to true
 */
function delete_undo_row(cell)
{
	var status = $(cell).children("input").val();
	if (status == "false")
	{
		$(cell).siblings().fadeTo("slow", 0.1);
		$(cell).children(".delete_undo").children().html("undo");
		$(cell).children("input").val("true");
	}
	else if (status == "true")
	{
		$(cell).siblings().fadeTo("slow", 1);
		$(cell).children(".delete_undo").children().html("delete");
		$(cell).children("input").val("false");
	}
}


