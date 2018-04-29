/*
	include/accounts/javascript/invoice-items-edit.js
	
	Various functions for powering the invoice items UI and providing
	AJAX functions for loading data.
*/
$(document).ready(function()
{

	$("select[name='productid']").change(function()
	{
		var id_product = $("select[name='productid'] option:selected").val();

		getProductData(id_product);
	});
	
	$("select[name='timegroupid']").change(function()
	{
		var id_timegroup= $("select[name='timegroupid'] option:selected").val();
		getTimeData(id_timegroup);
	});

	if ($("input[name='item_type']").val() == "time"){
		if ($("select[name='timegroupid'] option:selected").val() != 0)
		{
			getTimeData($("select[name='timegroupid'] option:selected").val());
		}
	}
});


/*
	getTimeData

	Populates the form with time group data
*/
function getTimeData(id_timegroup)
{
	$.getJSON("accounts/ajax/get_time_data.php", {id: id_timegroup}, 
			function(json)
			{
				$("textarea[name='description']").text(json['description']);
			}
	);
}



/*
	getProductData

	Populates a form with product data when an item is selected.
*/
function getProductData(id_product)
{
	$.getJSON("accounts/ajax/get_product_data.php", {id: id_product}, 
			function(json)
			{
				var type = $("input[name='item_type']").val();

				if (type == 'time')
				{
					$("input[name='price']").val(json['price_sale']);
					$("input[name='units']").val(json['units']);
					$("input[name='discount']").val(json['discount']);
				}
				else
				{
					$("input[name='price']").val(json['price_sale']);
					$("input[name='units']").val(json['units']);
					$("textarea[name='description']").text(json['details']);
					$("input[name='discount']").val(json['discount']);
				}
			}
	);	
}
