var num_trans;

$(document).ready(function()
{
	/*
		When any element in the last row is changed (therefore, having data put into it), call a function to create a new row
	*/
	num_trans = $("input[name='num_trans']").val();
	
	$("select[name^='trans_" + (num_trans-1) + "']").change(addTransactionRow);
	$("input[name^='trans_" + (num_trans-1) + "']").change(addTransactionRow);	 
	$("textarea[name^='trans_" + (num_trans-1) + "']").change(addTransactionRow);
	
	/*
	 * When an input field is changed, recalculate the debit and credit columns
	 */
	
	$("input[name^='trans_" + (num_trans-1) + "']").change(sumTotals);
	
	/*
	 * 	Calculate the initial sums for the debit and credit columns
	 */
	money_format = $("input[name='money_format']").val();
	credit_total = parseFloat($("input[name='total_credit']").val()).toFixed(2);
	debit_total  = parseFloat($("input[name='total_debit']").val()).toFixed(2);
	
	$("input[name='total_credit']").parent().append("<b>" + money_format.replace("0.00", credit_total) + "</b>");
	$("input[name='total_debit']").parent().append("<b>" + money_format.replace("0.00", debit_total) + "</b>");
});

function addTransactionRow()
{
	previous_row = $("select[name='trans_" + (num_trans-1) + "_account']").parent().parent();
	new_row = $(previous_row).clone().insertAfter(previous_row);
	$(new_row).children().children("select[name^='trans_" + (num_trans-1) + "_account']").removeAttr("name").attr("name", "trans_" + num_trans + "_account");
	//change names for other table cells
	$(new_row).children().children("input[name^='trans_" + (num_trans-1) + "_debit']").removeAttr("name").attr("name", "trans_" + num_trans + "_debit").val("");
	$(new_row).children().children("input[name^='trans_" + (num_trans-1) + "_credit']").removeAttr("name").attr("name", "trans_" + num_trans + "_credit").val("");
	$(new_row).children().children("input[name^='trans_" + (num_trans-1) + "_source']").removeAttr("name").attr("name", "trans_" + num_trans + "_source").val("");
	$(new_row).children().children("textarea[name^='trans_" + (num_trans-1) + "_description']").removeAttr("name").attr("name", "trans_" + num_trans + "_description").val("");
	
	//remove function calls from previous row
	$("select[name^='trans_" + (num_trans-1) + "']").unbind("change");
	$("input[name^='trans_" + (num_trans-1) + "']").unbind("change", addTransactionRow);
	$("textarea[name^='trans_" + (num_trans-1) + "']").unbind("change");
	
	//add one to num_tran
	num_trans++;
	$("input[name='num_trans']").val(num_trans);
	
	//add function calls to new row
	$("select[name^='trans_" + (num_trans-1) + "']").change(addTransactionRow);
	$("input[name^='trans_" + (num_trans-1) + "']").change(addTransactionRow);
	$("textarea[name^='trans_" + (num_trans-1) + "']").change(addTransactionRow);	
	
	$("input[name^='trans_" + (num_trans-1) + "']").change(sumTotals);
}

function sumTotals()
{
	credit_total = 0;
	debit_total = 0;
	for (i=0; i<=num_trans; i++)
	{
		if(!isNaN(parseFloat($("input[name='trans_" + i + "_credit']").val())))
		{
			credit = parseFloat($("input[name='trans_" + i + "_credit']").val()).toFixed(2);
			credit_total = (credit_total*1 + credit*1).toFixed(2);
		}
		if(!isNaN(parseFloat($("input[name='trans_" + i + "_debit']").val())))
		{
			debit = parseFloat($("input[name='trans_" + i + "_debit']").val()).toFixed(2);
			debit_total = (debit_total*1 + debit*1).toFixed(2);
		}
	}
	
	money_format = $("input[name='money_format']").val();

	//write new totals
		$("input[name='total_credit']").parent().children("b").replaceWith("<b>" + money_format.replace("0.00", credit_total) + "</b>");
		$("input[name='total_debit']").parent().children("b").replaceWith("<b>" + money_format.replace("0.00", debit_total) + "</b>");
}