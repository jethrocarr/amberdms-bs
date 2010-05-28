$(document).ready(function(){
	$("select[name='productid']").change(function(){
		var type = $("input[name='item_type']").val();
		var prod_id = $("select[name='productid'] option:selected").val();
		//if time
		if (type == 'time')
		{
			getTimeData(prod_id);
		}
		//if product
		else if (type == 'product')
		{
			getProductData(prod_id);
		}
	});
});

function getTimeData(prod_id)
{
	$.getJSON("../../../accounts/ajax/get_time_data.php", {id: prod_id}, 
			function(json)
			{
				alert(json);
			}
	);
}

function getProductData(prod_id)
{
	$.getJSON("accounts/ajax/get_product_data.php", {id: prod_id}, 
			function(json)
			{
				$("input[name='price']").val(json['price_sale']);
				$("input[name='units']").val(json['units']);
				$("textarea[name='description']").text(json['details']);
				$("input[name='discount']").val(json['discount']);
			}
	);	
}