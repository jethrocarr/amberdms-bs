$(document).ready(function()
{
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
});

// Toggle italics, opacity, image, tick mark, and drop down menus when a transaction is added or removed
function toggleIncludeTransaction(elem)
{
	if($(elem).hasClass("remove"))
	{
		$(elem).siblings().css("font-style", "italic").fadeTo("fast", 0.5);
		$(elem).siblings(".dropdown").children().attr("disabled", "disabled");
		$(elem).children().attr("src", "images/icons/plus.gif");
		$(elem).removeClass("remove").addClass("add");
		$(elem).siblings(".done").children().fadeOut("fast");
		$(elem).siblings(".dropdown").children("div:not('.assign')").fadeOut("fast");
	}
	else
	{
		$(elem).siblings().css("font-style", "normal").fadeTo("fast", 1);
		$(elem).siblings(".dropdown").children().removeAttr("disabled", "disabled");
		$(elem).children().attr("src", "images/icons/minus.gif");
		$(elem).removeClass("add").addClass("remove");
		if ($(elem).siblings(".dropdown").children(".assign").children().val() != "")
		{
			toggleSubMenus($(elem).siblings(".dropdown").children(".assign").children());
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
