/*
 * timereg-day-edit.js
 * 
 * Functions to enable dynamic features on the timekeeping page
 */

$(document).ready(function()
{
	//load phase dropdown when page loads
	projectid = $("select[name='projectid']").val();
	populate_phases_dropdown(projectid);
	
	//disable add_phase field if no project is selected 
	if ($("select[name='projectid']").val() == "")
	{
		$("input[name='add_phase']").val("Select a Project").attr("disabled", "disabled");
		$("#insert_phase").hide();
	}
	else
	{
		$("input[name='add_phase']").val("").removeAttr("disabled");
		$("#insert_phase").show();
	}
	
	//change phase dropdown and add_phase field when project dropdown is changed
	$("select[name='projectid']").change(function()
	{
		newprojectid = $(this).val();
		populate_phases_dropdown(newprojectid);
		
		if ($(this).val() == "")
		{
			$("input[name='add_phase']").val("Select a Project").attr("disabled", "disabled");
			$("#insert_phase").hide();
		}
		else
		{
			$("input[name='add_phase']").val("").removeAttr("disabled");
			$("#insert_phase").show();
		}
	});
	
	//show insert project or phase form
	$(".add_link").live("click", function()
	{
		show_add_project_phase(this.id);
		return false;
	});
	
	//hide insert project or phase form
	$(".cancel_link").live("click", function()
	{
		hide_add_project_phase(this.id);
		return false;
	});
	
	//insert new project or phase in DB when link/button is clicked
	$(".insert_project_phase").live("click", function()
	{
		if (this.id == "insert_project")
		{
			insert_new_project();
		}
		else
		{
			insert_new_phase();
		}
		return false;
	});
});


/*
 * Show fields to add projects and phases
 */
function show_add_project_phase(id)
{
	if (id == "project_add_cancel")
	{
		$("#toggle_add_project").show();
		$("#project_add_cancel").text("Cancel").removeClass("add_link").addClass("cancel_link");
		$("#add_project_box").addClass("add_box_open");
	}
	else
	{
		$("#toggle_add_phase").show();
		$("#phase_add_cancel").text("Cancel").removeClass("add_link").addClass("cancel_link");
		$("#add_phase_box").addClass("add_box_open");
	}
}


/*
 * Hide fields to add projects and phases
 */
function hide_add_project_phase(id)
{
	if (id == "project_add_cancel")
	{
		$("#toggle_add_project").hide();
		$("#project_add_cancel").text("Add New Project").removeClass("cancel_link").addClass("add_link");
		$("#add_project_box").removeClass("add_box_open");
		$("input[name='add_project']").val("");
	}
	else
	{
		$("#toggle_add_phase").hide();
		$("#phase_add_cancel").text("Add Phase to Current Project").removeClass("cancel_link").addClass("add_link");
		$("#add_phase_box").removeClass("add_box_open");
		$("input[name='add_phase']").val("");
	}
}

/*
 * Insert new projects into DB via AJAX and repopulate dropdown
 */
function insert_new_project()
{
	if ($("input[name=\"add_project\"]").val().length > 0)
	{
		name_project = $("input[name=\"add_project\"]").val();
		$.get("projects/ajax/insert_new_project.php", {name_project : name_project}, function(text)
		{
			projectid = text;
			
			//create new projects dropdown
			$.get("timekeeping/ajax/populate_projects_dropdown.php", {selected_project : projectid}, function(text)
			{
				$("select[name='projectid']").html(text);
			});
		});
		
		$("input[name=\"add_project\"]").val("");
		hide_add_project_phase("project_add_cancel");
		
		//disable phase dropdown until new phases added to new project
		$("select[name='phaseid']").html("<option value=\"\"> -- there are no phases associated with this project -- </option>").attr("disabled", "disabled");
		$("#_phaseid").val("").attr("disabled", "disabled");

		// enable the add_phase field if it has been disabled
		$("input[name='add_phase']").val("").removeAttr("disabled");
		$("#insert_phase").show();
	}
}


/*
 * Insert new phases into DB via AJAX
 */
function insert_new_phase()
{
	if ($("input[name=\"add_phase\"]").val().length > 0)
	{
		name_phase = $("input[name=\"add_phase\"]").val();
		projectid = $("select[name='projectid']").val();
		
		$.get("projects/ajax/insert_new_phase.php", {name_phase: name_phase, projectid: projectid}, function(text)
		{
			phaseid = text;
			
			//call function to populate new phases dropdown
			populate_phases_dropdown(projectid, phaseid);
		});
		
		$("input[name=\"add_phase\"]").val("");
		hide_add_project_phase("phase_add_cancel");
	}
}


/*
 * uses ajax to produce phases dropdown menu according to the project id
 */
function populate_phases_dropdown(projectid, phaseid, load)
{
	timereg_id = $("input[name = \"id_timereg\"]").val();
	edit = "false";

	if (projectid == "")
	{
		$("select[name='phaseid']").html("<option value=\"\"> -- choose a project -- </option>").attr("disabled", "disabled");
	}
	else
	{
		//set variables so AJAX knows what phase to select, if any
		//if a phaseid exists, it is selected over the one stored against the timereg value
		if (phaseid)
		{
			selected = phaseid;
		}
		else if (timereg_id > 0)
		{
			selected = "";
			edit = "true"; //indicates stored values should be shown
		}
		else
		{
			selected = "";
		}
		
		$.get("timekeeping/ajax/populate_phases_dropdown.php", {project_id: projectid, selected: selected, edit: edit, timereg_id: timereg_id}, 
				function(text)
				{
					//insert new options and enable dropdown
					$("select[name='phaseid']").html(text).removeAttr("disabled");
					
					//select first in list if an option is not already selected
					if (!$("select[name='phaseid'] option.selected").length)
					{
						$("select[name='phaseid'] option[value='1']").attr("selected", "selected");
					}
					
					//if no options exist, disable dropdown
					if ($("select[name='phaseid']").val() == "")
					{
						$("select[name='phaseid']").attr("disabled", "disabled");
						$("#_phaseid").attr("disabled", "disabled").val("");
					}
					else
					{
						$("#_phaseid").removeAttr("disabled");
					}
					
					//add dropdown to the dropdown array to enable searching
					dropdown_array["phaseid"] = $("select[name='phaseid']").clone(true);
				}
		);		
	}
}
