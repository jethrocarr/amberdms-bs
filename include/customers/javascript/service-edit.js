var num_ddi_rows

$(document).ready(function()
{
	/*
		When any element in the last row is changed (therefore, having data put into it), call a function to create a new row
	*/
	num_ddi_rows = $("input[name='num_ddi_rows']").val();
	
	$("input[name='ddi_start_" + (num_ddi_rows-1) + "']").change(addRow);	
	$("input[name='ddi_finish_" + (num_ddi_rows-1) + "']").change(addRow);
	$("textarea[name='description_" + (num_ddi_rows-1) + "']").change(addRow);
	
	/*
	 	Delete or undo deletion of a DDI range
	 */
	$("a[id^='delete_link_']").click(function()
	{
		id = this.id.substr(12);
		deleteRow(id);
		return false;
	});
	
	
	/*
	 	In case of an error, check if any rows need to appear as deleted
	 */
	$("input[name^='delete_']").each(function()
	{
		if ($(this).val() == "true")
		{
			id = $(this).attr("name").substr(7);
			
			$("input[name='ddi_start_" + id + "']").parent().fadeTo("slow", 0.1);
			$("input[name='ddi_finish_" + id + "']").parent().fadeTo("slow", 0.1);
			$("textarea[name='description_" + id + "']").parent().fadeTo("slow", 0.1);
			
			$("input[name='ddi_start_" + id + "']").attr("disabled", "disabled");
			$("input[name='ddi_finish_" + id + "']").attr("disabled", "disabled");
			$("textarea[name='description_" + id + "']").attr("disabled", "disabled");
			
			$("input[name='delete_" + id + "']").val("true");
			$("#delete_link_" + id).text("undo");
		}
	});
});

function addRow()
{
	previous_row = $("input[name='delete_" + (num_ddi_rows-1) + "']").parent().parent();
	new_row = $(previous_row).clone().insertAfter(previous_row);
	
	$(new_row).children().children("input[name='ddi_start_" + (num_ddi_rows-1) + "']").removeAttr("name").attr("name", "ddi_start_" + num_ddi_rows).val("");
	$(new_row).children().children("input[name='ddi_finish_" + (num_ddi_rows-1) + "']").removeAttr("name").attr("name", "ddi_finish_" + num_ddi_rows).val("");
	$(new_row).children().children("input[name='ddi_id_" + (num_ddi_rows-1) + "']").removeAttr("name").attr("name", "id_" + num_ddi_rows).val("");
	$(new_row).children().children("input[name='delete_" + (num_ddi_rows-1) + "']").removeAttr("name").attr("name", "delete_" + num_ddi_rows).val("false");
	$(new_row).children().children("textarea[name='description_" + (num_ddi_rows-1) + "']").removeAttr("name").attr("name", "description_" + num_ddi_rows).val("");
	
	//remove function calls from previous row
	$("input[name='ddi_start_" + (num_ddi_rows-1) + "']").unbind("change");
	$("input[name='ddi_finish_" + (num_ddi_rows-1) + "']").unbind("change");
	$("textarea[name='description_" + (num_ddi_rows-1) + "']").unbind("change");
	
	//add one to num_tran
	num_ddi_rows++;
	$("input[name='num_ddi_rows']").val(num_ddi_rows);
	
	//add function calls to new row
	$("input[name='ddi_start_" + (num_ddi_rows-1) + "']").change(addRow);
	$("input[name='ddi_finish_" + (num_ddi_rows-1) + "']").change(addRow);
	$("textarea[name='description_" + (num_ddi_rows-1) + "']").change(addRow);	
}

function deleteRow(id)
{
	
	//delete
	if ($("input[name='delete_" + id + "']").val() == "false")
	{
		$("input[name='ddi_start_" + id + "']").parent().fadeTo("slow", 0.1);
		$("input[name='ddi_finish_" + id + "']").parent().fadeTo("slow", 0.1);
		$("textarea[name='description_" + id + "']").parent().fadeTo("slow", 0.1);
		
		$("input[name='ddi_start_" + id + "']").attr("disabled", "disabled");
		$("input[name='ddi_finish_" + id + "']").attr("disabled", "disabled");
		$("textarea[name='description_" + id + "']").attr("disabled", "disabled");
		
		$("input[name='delete_" + id + "']").val("true");
		$("#delete_link_" + id).text("undo");
	}
	else
	{
		$("input[name='ddi_start_" + id + "']").parent().fadeTo("slow", 1);
		$("input[name='ddi_finish_" + id + "']").parent().fadeTo("slow", 1);
		$("textarea[name='description_" + id + "']").parent().fadeTo("slow", 1);
		
		$("input[name='ddi_start_" + id + "']").removeAttr("disabled");
		$("input[name='ddi_finish_" + id + "']").removeAttr("disabled");
		$("textarea[name='description_" + id + "']").removeAttr("disabled");
		
		$("input[name='delete_" + id + "']").val("false");
		$("#delete_link_" + id).text("delete");
	}
}