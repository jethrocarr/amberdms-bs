/*
	include/language/translate_ui.js

	Javascript for controling the language translation window popup.


	Based off sample code from:
	http://yensdesign.com/2008/09/how-to-create-a-stunning-and-smooth-popup-using-jquery/
	http://stackoverflow.com/questions/815202/insert-selected-text-on-the-page-into-input-jquery
*/


// control popup status
// 1 == on, 0 == off
var trans_popup_status = 0;



/*
	Functions to control the popup window
*/

function trans_popup_load()
{
	if(trans_popup_status == 0)
	{
		$("#trans_popup_background").css(
		{
			"opacity": "0.7"
		});

		$("#trans_popup_background").fadeIn("slow");

		$("#trans_popup").fadeIn("slow");


		// we use the timeout to prevent the keystroke from javascript being
		// entered into the field when we focus on it
		setTimeout(function(){
			$("input[name='trans_label']").focus();
		},200);



		trans_popup_status = 1;  
	}
}


function trans_popup_disable()
{
	//disables popup only if it is enabled
	if (trans_popup_status == 1)
	{
		$("#trans_popup_background").fadeOut("slow");
		$("#trans_popup").fadeOut("slow");

		trans_popup_status = 0;
	}
}

function trans_popup_center()
{
	var windowWidth = document.documentElement.clientWidth;  
	var windowHeight = document.documentElement.clientHeight;  

	var popupHeight = $("#trans_popup").height();  
 	var popupWidth = $("#trans_popup").width();  

	//centering  
	$("#trans_popup").css({  
		"position": "absolute",  
		"top": windowHeight/2-popupHeight/2,  
		"left": windowWidth/2-popupWidth/2  
	});

	// IE 6 fixes
//	$("#trans_popup").css({  
//		"height": windowHeight  
//	});
}


/*
	Functions to select the dropdown text
*/

function trans_get_selected_text()
{
	/*
		Get the text if possible
	*/

	var selected_text;

	if (window.getSelection)
	{
		selected_text = window.getSelection();
	}
	else if (document.selection)
	{
		selected_text = document.selection.createRange().text;
	}
	else
	{
		return '';
	}


	/*
		Attempt to match known translation label fields and strip out other content
	*/

	selected_text = selected_text.toString();

	// match any strings in form of:  junk (label) junk
	pattern	= /\((\S*)\)/;
	matches	= selected_text.match(pattern);
	
	if (matches)
	{
		match1	= matches[1];

		if (match1)
		{
			return match1;
		}
	}


	// match any strings in form of:  junk [[label]] junk
	pattern	= /\[\[(\S*)\]\]/;
	matches	= selected_text.match(pattern);
	
	if (matches)
	{
		match1	= matches[1];

		if (match1)
		{
			return match1;
		}
	}



	return selected_text;
}


function trans_get_translation(trans_label)
{
	if (trans_label)
	{
		// fetch translation
		$.get("language/ajax/trans_fetch_translation.php", {trans_label: trans_label}, function(text)
		{
			trans_translation = text;
		
			// update transation field
			$("input[name=\"trans_translation\"]").val(trans_translation);
		});
		
	}

	return 0;
}


/*
	Control the loading/hiding of the translation popup window
*/
$(document).ready(function(){  

	/*	
		Activation of popup window (via button)
	*/
	$("#trans_popup_activate").click(function()
	{  
		// copy any selected text
		var input = jQuery('input#trans_label');
		input.val(trans_get_selected_text());

		// fetch translation for label (if any exists)
		trans_get_translation(input.val());

		// center the window
		trans_popup_center();  

		// display the popup
		trans_popup_load();
	});



	/*
		Close the popup window
	*/

	// close button
	$("#trans_popup_close").click(function()
	{
		trans_popup_disable();
	});
	
	// click out of the window
	$("#trans_popup_background").click(function()
	{
		trans_popup_disable();
	});




	/*
		Key Handling
	*/
	$(document).keydown(function(e)
	{
		/*
			Terminate popup on reciept of <ESC> key
		*/

		if (e.keyCode==27 && trans_popup_status==1)
		{
			trans_popup_disable();
		}


		/*
			Activation of popup window (via ` key)
		*/

		if (e.keyCode==192)
		{
			if (trans_popup_status==1)
			{
				// hide translation window
				trans_popup_disable();
			}
			else
			{
				// copy any selected text
				var input = jQuery('input#trans_label');
				input.val(trans_get_selected_text());

				// fetch translation for label (if any exists)
				trans_get_translation(input.val());

				// center the window
				trans_popup_center();

				// display translation window
				trans_popup_load();
			}
		}

	});




	/*
		Form Submission Processing

		We use AJAX for the form submission, to prevent the user from having to reload
		the whole page just to make one translation submission.
	*/

	$("#trans_submit").click(function()
	{

		// fetch values
		var input;

		input = jQuery('input#trans_label');
		var trans_label		= input.val();

		input = jQuery('input#trans_translation');
		var trans_translation	= input.val();



		// submit translation
		$.get("language/ajax/trans_update_translation.php", {trans_label: trans_label, trans_translation: trans_translation}, function(text)
		{
			/*
			// return information
			if (text == "failure")
			{
				// gray out the popup
				$("#trans_popup").css(
				{
					"opacity": "0.7"
				});

				return true;
			}
			else
			{

			}
			*/

			// terminate the popup
			trans_popup_disable();
		});


		// update the translations displayed on the active page
		document.body.innerHTML = document.body.innerHTML.replace("[[" + trans_label + "]]", "{{" + trans_translation + "}} (" + trans_label + ")");


		return false;	
	});

});


