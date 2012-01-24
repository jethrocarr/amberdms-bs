/*
	include/vendors/javascript/addedit_customers.js

	Provides Javascript UI functions for the customer details forms.
*/

$(document).ready(function() {
	$("tr.shipping_same_address input[type=checkbox]").change(function() {
		if($(this).attr('checked') == true) {
			$('tr.shipping_address td :input').attr('disabled', 'disabled');
		} else {
			$('tr.shipping_address td :input').removeAttr('disabled');
		}
	});

	// set the reseller id to 0 and disable it if the reseller is enabled
	if($(":input[name='reseller_customer']:checked").val() !== "customer_of_reseller") {
		$(":input[name='reseller_id']").val('0');
		$(":input[name='reseller_id']").attr('disabled', 'disabled');
	}

	// if the reseller is selected or deselected, then disable/enable the reseller customer dropdown
	$(":input[name='reseller_customer']").change(function() {
		if($(this).val() === "customer_of_reseller") {
			$(":input[name='reseller_id']").removeAttr('disabled');
		} else {
			$(":input[name='reseller_id']").attr('disabled', 'disabled');
		}
	});

	billing_street = $(":input[name='address1_street']").val();
	billing_city = $(":input[name='address1_city']").val();
	billing_state = $(":input[name='address1_state']").val();
	billing_country = $(":input[name='address1_country']").val();
	billing_zipcode = $(":input[name='address1_zipcode']").val();
	
	billing_test_string = billing_street + billing_city + billing_state + billing_country + billing_zipcode;

	if((billing_test_string != '') && (billing_test_string != 'undefined')) {
		shipping_street = $(":input[name='address2_street']").val();
		shipping_city = $(":input[name='address2_city']").val();
		shipping_state = $(":input[name='address2_state']").val();
		shipping_country = $(":input[name='address2_country']").val();
		shipping_zipcode = $(":input[name='address2_zipcode']").val();
		shipping_test_string = "" + shipping_street + shipping_city + shipping_state + shipping_country + shipping_zipcode;
		
		if(billing_test_string == shipping_test_string) {
			$('tr.shipping_address td :input').attr('disabled', 'disabled');
			$("tr.shipping_same_address input[type=checkbox]").attr('checked', ' checked');
		}
	}
});
