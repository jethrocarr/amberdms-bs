$(document).ready(function()
{
//     When remove button is clicked:
// 		*italisize row
// 		*lighten text colour
// 		*disable drop down
// 		*change '-' to '+'
// 		*hide tick mark
    $(".toggle_include").click(function()
    {
	toggleIncludeTransaction($(this));
    });  
});

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
/*
function addTransaction(elem)
{
	$(elem).siblings().css("font-style", "normal").fadeTo("fast", 1);
	$(elem).siblings(".dropdown").children().removeAttr("disabled", "disabled");
	$(elem).children().attr("src", "images/icons/minus.gif");
	$(elem).removeClass("add").addClass("remove");
	$(elem).siblings(".done").children().show("fast");
// 	$(elem).click(function()
// 	{
// 	    removeTransaction(elem);
// 	});
}*/