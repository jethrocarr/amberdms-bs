$(document).ready(function()
{
	$("div[class^='toggle']").hide();
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
		$(elem).siblings(".done").children().hide("fast");
	}
	else
	{
		$(elem).siblings().css("font-style", "normal").fadeTo("fast", 1);
		$(elem).siblings(".dropdown").children().removeAttr("disabled", "disabled");
		$(elem).children().attr("src", "images/icons/minus.gif");
		$(elem).removeClass("add").addClass("remove");
		$(elem).siblings(".done").children().show("fast");
	}
}


function toggleSubMenus(elem)
{
	assigndiv = $(elem).parent();
	trans_type = $(elem).val();
	$(assigndiv).siblings("div[class$='" + trans_type + "']").show();
	$(assigndiv).siblings("div:not([class$='" + trans_type + "'])").hide();
}