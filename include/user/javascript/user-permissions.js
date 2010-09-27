$("document").ready(function()
{
	//tick and untick groups in bulk
	$("input[id^='check_all_in_']").click(function()
	{		
		group 		= this.id.substr(13);
		num_perms 	= $("input[name='num_perms_in_" + group + "']").val();

		if ($(this).attr("checked"))
		{
			$(".perm_group_" + group).attr("checked", true);
			$("input[name='num_ticked_in_" + group + "']").val(num_perms);
		}
		else
		{
			$(".perm_group_" + group).attr("checked", false);
			$("input[name='num_ticked_in_" + group + "']").val(0);
		}
	});
	
	//change 'num_ticked' amount when selection changes
	//change 'tick_all' if applicable
	$("input[class^='perm_group']").click(function()
	{
		group 		= $(this).attr("class").substr(11);
		num_perms 	= $("input[name='num_perms_in_" + group + "']").val();
		num_ticked	= $("input[name='num_ticked_in_" + group + "']").val();

		//change num_ticked count
		if ($(this).attr("checked"))
		{
			num_ticked++;
			$("input[name='num_ticked_in_" + group + "']").val(num_ticked);
		}
		else
		{
			num_ticked--;
			$("input[name='num_ticked_in_" + group + "']").val(num_ticked);
		}
		
		//change 'tick all' if needed
		if (num_perms == num_ticked)
		{
			$("input[name='check_all_in_" + group + "']").attr("checked", true);
		}
		else
		{
			$("input[name='check_all_in_" + group + "']").attr("checked", false);
		}
	});
});