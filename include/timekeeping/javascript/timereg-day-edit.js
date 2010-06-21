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
	
	//change phase dropdown when project dropdown is changed
	$("select[name='projectid']").change(function()
	{
		newprojectid = $(this).val();
		populate_phases_dropdown(newprojectid);
	});
});

/*
 * populate_phases_dropdown
 * 
 * uses ajax to produce phases dropdown menu according to the project id
 */
function populate_phases_dropdown(projectid)
{
	timereg_id = $("input[name = \"id_timereg\"]").val();
	
	if (projectid == "")
	{
		$("select[name='phaseid']").html("<option value=\"\"> -- choose a project -- </option>").attr("disabled", "disabled");
	}
	else
	{
		$.get("include/timekeeping/ajax/populate_phases_dropdown.php", {project_id: projectid, timereg_id: timereg_id}, 
				function(text)
				{
					$("select[name='phaseid']").html(text).removeAttr("disabled");
				}
		);
	}
		
}