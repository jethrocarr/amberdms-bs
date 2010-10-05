$(document).ready(function()
{

	//disable a link
	$(".disabled_link").live("click", function()
	{
		return false;
	});
	
	
	//reenable disabled fields on submit so that all data is submitted
	$("input[name='submit']").click(function()
	{
		$("input").removeAttr("disabled");
		$("textarea").removeAttr("disabled");
		$("select").removeAttr("disabled");
	});
	
	
	//if a contact is set to be deleted, change the display
	$("input[name^='delete_contact_']").each(function()
	{
		if ($(this).val() == "true")
		{
			id = $(this).attr("name").substr(15);

			$("#contact_box_" + id).children().children().children().not(".delete_contact_cell").fadeTo("fast", "0.1").css("font-style", "italic")	;
			$("#delete_contact_" + id).text("undo").css("font-weight", "bold");
			$("a[id^='contact_" + id + "_delete_']").addClass("disabled_link");
			$("#add_record_" + id).addClass("disabled_link");
			$("#change_contact_" + id).addClass("disabled_link");
			$("input[id^='contact_" + id + "_detail_']").attr("disabled", "disabled");
			$("select[name=role_" + id + "']").attr("disabled", "disabled");
			$("#contact_" + id).attr("disabled", "disabled");
			$("textarea[name^='description_" + id + "']").attr("disabled", "disabled");
		}
	});
	
	
	//if a record is set to be deleted, change the display
	$("input[name*='_delete_']").each(function()
	{
		if ($(this).val() == "true")
		{
			id_array = $(this).attr("name").split("_");
			contact_id = id_array[1];
			record_id = id_array[3];
			
			$("#contact_" + contact_id + "_record_row_" + record_id).children().not(".delete_record").fadeTo("slow", "0.1").css("font-style", "italic");
			$("input[name='contact_" + contact_id + "_delete_" + record_id + "']").val("true");
			$("#contact_" + contact_id + "_delete_" + record_id).text("undo").css("font-weight", "bold");			
			$("#contact_" + contact_id + "_detail_" + record_id).attr("disabled", "disabled");
		}
	});
	
	

	//delete contact
	$("a[id^='delete_contact_']").live("click", function()
	{
		id = this.id.substr(15);

		if ($("input[name='delete_contact_" + id + "']").val() == "false")
		{
			$("#contact_box_" + id).children().children().children().not(".delete_contact_cell").fadeTo("slow", "0.1").css("font-style", "italic")	;	
			$("input[name='delete_contact_" + id + "']").val("true");
			$(this).text("undo").css("font-weight", "bold");
			
			$("a[id^='contact_" + id + "_delete_']").addClass("disabled_link");
			$("#add_record_" + id).addClass("disabled_link");
			$("#change_contact_" + id).addClass("disabled_link");
			$("input[id^='contact_" + id + "_detail_']").attr("disabled", "disabled");
			$("#contact_" + id).attr("disabled", "disabled");
			$("select[name=role_" + id + "']").attr("disabled", "disabled");
			$("textarea[name^='description_" + id + "']").attr("disabled", "disabled");
		}
		
		else if ($("input[name='delete_contact_" + id + "']").val() == "true")
		{
			$("#contact_box_" + id).children().children().children().not(".delete_contact_cell").fadeTo("slow", "1").css("font-style", "normal")	;	
			$("input[name='delete_contact_" + id + "']").val("false");
			$(this).text("delete contact...").css("font-weight", "normal");
			
			$("a[id^='contact_" + id + "_delete_']").removeClass("disabled_link");
			$("#add_record_" + id).removeClass("disabled_link");
			$("#change_contact_" + id).removeClass("disabled_link");
			$("input[id^='contact_" + id + "_detail_']").removeAttr("disabled");
			$("#contact_" + id).removeAttr("disabled");
			$("select[name=role_" + id + "']").removeAttr("disabled");
			$("textarea[name^='description_" + id + "']").removeAttr("disabled");
		}
		
		return false;
	});
	
	
	//delete record or undo record
	$("a[id*='_delete_']").live("click", function()
	{
		id_array = this.id.split("_");
		contact_id = id_array[1];
		record_id = id_array[3];
		
		if ($("input[name='contact_" + contact_id + "_delete_" + record_id + "']").val() == "false")
		{
			$("#contact_" + contact_id + "_record_row_" + record_id).children().not(".delete_record").fadeTo("slow", "0.1").css("font-style", "italic");
			$("input[name='contact_" + contact_id + "_delete_" + record_id + "']").val("true");
			$(this).text("undo").css("font-weight", "bold");			
			$("#contact_" + contact_id + "_detail_" + record_id).attr("disabled", "disabled");
		}
		
		else if ($("input[name='contact_" + contact_id + "_delete_" + record_id + "']").val() == "true")
		{
			$("#contact_" + contact_id + "_record_row_" + record_id).children().not(".delete_record").fadeTo("slow", "1").css("font-style", "normal");
			$("input[name='contact_" + contact_id + "_delete_" + record_id + "']").val("false");
			$(this).text("delete").css("font-weight", "normal");
			$("#contact_" + contact_id + "_detail_" + record_id).removeAttr("disabled", "disabled");
		}
		
		return false;
	});
	
	
	
	//edit contact
	$("a[id^='change_contact_']").live("click", function()
	{
		id = this.id.substr(15);
		
		if ($("input[name='change_contact_" + id + "']").val() == "closed")
		{
			$("#contact_text_" + id).hide();
			$("#description_text_" + id).hide();
			$("input[name='contact_" + id + "']").show();
			$("label[for='contact_" + id + "']").parent("span").show();
			$("textarea[name='description_" + id + "']").show();
			$("label[for='description_" + id + "']").parent("span").show();
			$("select[name='role_" + id + "']").show();
			$("label[for='role_" + id + "']").parent("span").show();
			$("input[name='change_contact_" + id + "']").val("open");
			$("#change_contact_" + id).text("done");
		}
		
		else if ($("input[name='change_contact_" + id + "']").val() == "open")
		{
			contact = $("input[name='contact_" + id + "']").val();
			description = $("textarea[name='description_" + id + "']").val();
			role = $("select[name='role_" + id + "']").val()
			
			$("#contact_text_" + id).html("<b>" + contact + "</b><br />(" + role + ")").show();
			$("#description_text_" + id).text(description).show();
			$("input[name='contact_" + id + "']").hide();
			$("label[for='contact_" + id + "']").parent("span").hide();
			$("textarea[name='description_" + id + "']").hide();
			$("label[for='role_" + id + "']").parent("span").hide();
			$("select[name='role_" + id + "']").hide();
			$("label[for='description_" + id + "']").parent("span").hide();
			$("input[name='change_contact_" + id + "']").val("closed");
			$("#change_contact_" + id).text("change...");
		}
		
		return false;
	});
	
	
	//add record
	$("a[id^='add_record_']").live("click", function()
	{
		id = this.id.substr(11);
		
		$("#add_record_link_" + id).hide();
		$("#add_record_form_" + id).show();
		
		return false; 
	});
	
	
	//insert record
	$("a[id^='insert_record_']").live("click", function()
	{
		c_id = parseInt(this.id.substr(14));
		r_id = parseInt($("input[name='num_records_" + c_id + "']").val());
		
		type = $("select[name='new_record_type_" + c_id + "']").val();
		label = $("input[name='new_record_label_" + c_id + "']").val();
		detail = $("input[name='new_record_detail_" + c_id + "']").val();
		contact_id = $("input[name='contact_id_" + c_id + "']").val();
		
		$("input[name='num_records_" + c_id + "']").val(r_id + 1);
		
		html_string = "<tr id=\"contact_" + c_id + "_record_row_" + r_id + "\">" +
						"<td>" +
							"<input type=\"hidden\" value=\"\" name=\"contact_" + c_id + "_record_id_" + r_id + "\">" +
							"<input type=\"hidden\" value=\"" + type + "\" name=\"contact_" + c_id + "_type_" + r_id + "\">";
		
							if (type == "phone")
							{
								html_string += "<b>P</b>";
							}
							else if (type == "fax")
							{
								html_string += "<b>F</b>";
							}
							else if (type == "mobile")
							{
								html_string += "<b>M</b>";
							}
							else if (type == "email")
							{
								html_string += "<b>E</b>";
							}
							
		html_string += "</td><td>" +
							"<input type=\"hidden\" value=\"" + label + "\" name=\"contact_" + c_id + "_label_" + r_id + "\">" +
							label +
						"</td><td>" +
							"<input id=\"contact_" + c_id + "_detail_" + r_id + "\" value=\"" + detail + "\" name=\"contact_" + c_id + "_detail_" + r_id + "\">" +
						"</td><td class=\"delete_record\">" +
							"<input type=\"hidden\" value=\"false\" name=\"contact_" + c_id + "_delete_" + r_id + "\">" +
							"<a id=\"contact_" + c_id + "_delete_" + r_id + "\" href=\"\">delete</a>" +
						"</td></tr>"; 
		
		$("#records_table_" + c_id).append(html_string);
		
		$("select[name='new_record_type_" + c_id + "']").val("");
		$("input[name='new_record_label_" + c_id + "']").val("");
		$("input[name='new_record_detail_" + c_id + "']").val("");
		
		$("#add_record_link_" + c_id).show();
		$("#add_record_form_" + c_id).hide();
		$("#insert_record_" + c_id).addClass("disabled_link");
	});
	
	
	//add new contact
	$("#add_new_contact").click(function()
	{
		c_id = parseInt($("input[name='num_contacts']").val());
		
		$("input[name='num_contacts']").val(c_id + 1);
		
		html_string = "<tr><td>" +
						"<table id=\"contact_box_" + c_id + "\" class=\"contact_box\">" +
						
						"<tr><td width=\"25%\">" +
							"<input type=\"hidden\" value=\"\" name=\"contact_id_" + c_id + "\">" +
							"<span class=\"hidden_text\">" +
								"<label for=\"contact_" + c_id + "\">Name: </label><br />" +
							"</span>" +
							"<input id=\"contact_" + c_id + "\" class=\"hidden_form_field new_field\" style=\"width:200px;\" value=\"\" name=\"contact_" + c_id + "\">" +
							"<span class=\"hidden_text\">" +
								"<br /><label for=\"role_" + c_id + "\">Role: </label><br />" +
							"</span>" +
							"<select class=\"hidden_form_field\" style=\"width: 205px;\" name=\"role_" + c_id + "\">" +
								"<option value=\"other\" selected=\"\">other</option>" +
							"</select>" +
							"<input type=\"hidden\" value=\"0\" name=\"num_records_" + c_id + "\">" +
							"<div id=\"contact_text_" + c_id + "\">" +
								"<b>&nbsp;</b>" +
								"<br />" +
								"(other)" +
							"</div>" +
							
						"</td><td class=\"delete_contact_cell\" width=\"75%\">" +
							"<input type=\"hidden\" value=\"false\" name=\"delete_contact_" + c_id + "\">" +
							"<a id=\"delete_contact_" + c_id + "\" href=\"\">delete contact...</a>" +
						"</td></tr><tr>" +
						
						"<td class=\"description_cell\" width=\"25%\">" +
							"<span class=\"hidden_text\">" +
								"<label for=\"description_" + c_id + "\">Description: </label><br />" +
							"</span>" +
							"<textarea class=\"hidden_form_field new_field\" style=\"width:205px;\" name=\"description_" + c_id + "\"></textarea>" +
							"<p id=\"description_text_" + c_id + "\" class=\"contact_description\">&nbsp;</p>" +
							"<p class=\"change_contact\">" +
								"<a id=\"change_contact_" + c_id + "\" href=\"\">done</a>" +
							"</p>" +
							"<input type=\"hidden\" value=\"open\" name=\"change_contact_" + c_id + "\">" +
							
						"</td><td width=\"75%\" align=\"right\">" +
							"<table id=\"records_table_" + c_id + "\" class=\"records_table\"></table><br />" +
							
							"<div class=\"add_record\">" +
								"<div id=\"add_record_link_" + c_id + "\">" +
									"<a id=\"add_record_link_" + c_id + "\" href=\"\">Add Record</a>" +
									
								"</div><div id=\"add_record_form_" + c_id + "\" class=\"add_record_form\">" +
									"<table class=\"add_record_table\">" +
									
										"<tr><td colspan=\"2\">" +
											"<b>Add Record</b>" +
										"</td></tr>" +
										
										"<tr><td>" +
											"Record Type" +
											
										"</td><td>" +
											"<select name=\"new_record_type_" + c_id + "\">" +
												"<option value=\"phone\">Phone</option>" +
												"<option value=\"fax\">Fax</option>" +
												"<option value=\"mobile\">Mobile</option>" +
												"<option value=\"email\">Email</option>" +
											"</select>" +
										"</td></tr>" +
										
										"<tr><td>" +
											"Label" +
											
										"</td><td>" +
											"<input name=\"new_record_label_" + c_id + "\">" +
										"</td></tr>" +
										
										"<tr><td>" +
											"Detail" +
											
										"</td><td>" +
											"<input name=\"new_record_detail_" + c_id + "\">" +
										"</td></tr>" +
										
										"<tr><td class=\"insert_new_record\" colspan=\"2\">" +
											"<a id=\"insert_record_" + c_id + "\" class=\"disabled_link button_small\">Insert</a>" +
											
										"</td></tr>" +
									"</table>" +
								"</div>" +
							"</div>" +
						"</td></tr>" +
						"</table>" +
					"</td></tr>";
		
		$("#add_new_contact_row").before(html_string);
		
		$("#contact_text_" + c_id).hide();
		$("#description_text_" + c_id).hide();		
		$("input[name='contact_" + c_id + "']").show();
		$("label[for='contact_" + c_id + "']").parent("span").show();
		$("textarea[name='description_" + c_id + "']").show();
		$("label[for='description_" + c_id + "']").parent("span").show();
		$("select[name='role_" + c_id + "']").show();
		$("label[for='role_" + c_id + "']").parent("span").show();
		$("#add_record_link_" + c_id).hide();
		$("#add_record_form_" + c_id).show();
				
		return false;
	});
	
	
	//empty fields on click when the content is instructions
	$(".new_field").live("click", function()
	{
		$(this).val("");
		$(this).removeClass("new_field");
	});

	
	//don't allow user to insert record if no label has been given
	$("input[name^='new_record_label_']").live("change", function()
	{
		id = $(this).attr("name").substr(17);

		if ($(this).val().length > 0)
		{
			$("#insert_record_" + id).removeClass("disabled_link");
		}
		else
		{
			$("#insert_record_" + id).addClass("disabled_link");
		}			
	});
	
	
	
	//show insert record form in add customer if fresh form
	$("#add_record_new_customer").each(function()
	{
		$("#add_record_link_0").hide();
		$("#add_record_form_0").show();
	});
});