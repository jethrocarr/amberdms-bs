#!/usr/bin/php
<?php
/*
	include/repair/list_call_rate_differences.php

	Takes the provided customer-service ID and generates a CSV output and total
	credit recommendation based on incorrectly billed call records.
*/


// custom includes
require("../accounts/inc_ledger.php");
require("../accounts/inc_invoices.php");
require("../accounts/inc_credits.php");
require("../services/inc_services.php");
require("../services/inc_services_cdr.php");
require("../customers/inc_customers.php");



function page_execute($argv)
{
	/*
		Input Options
	*/

	$id_service_customer  = $argv[2];

	if (empty($argv[2]))
	{
		die("id_service_customer must be set\n");
	}


	$output_file = $argv[3];

	if (empty($argv[3]))
	{
		die("An output file must be specific for CVS output");
	}




	/*
		Fetch customer details
	*/

	$id_customer	= sql_get_singlevalue("SELECT customerid as value FROM services_customers WHERE id='$id_service_customer' LIMIT 1");
	

	if (!$id_customer)
	{
		// no id_customer, return fail
		die("invalid id_service_customer, no matching customer entry found\n");
	}

	log_write("notification", "repair", "Reviewing call rate charges for customer id $id_customer with id_service_customer id $id_service_customer");



	/*
		Write File Header
	*/

	$handle_file_csv = fopen($output_file, 'w');
	
	if (!$handle_file_csv)
	{
		die("Unable to open file $output_file for writing");
	}

	fwrite($handle_file_csv, '"Date";"Source";"Destination";"Num Seconds";"Original Price";"New Price";"Refund Amount";"Original Billgroup";"New Billgroup";'. "\n");

	log_write("notification", "repair", "Writing call details to file $output_file");




	/* 
		Load Call Pricing (including overrides for this customer)
	*/

	$obj_cdr_rate_table			= New cdr_rate_table_rates_override;

	$obj_cdr_rate_table->option_type	= "customer";
	$obj_cdr_rate_table->option_type_id	= $id_service_customer;

	$obj_cdr_rate_table->verify_id_override();

	$obj_cdr_rate_table->load_data();

	$obj_cdr_rate_table->load_data_rate_all();
	$obj_cdr_rate_table->load_data_rate_all_override();


	/*
		We need a list of the DDIs for this customer.
	*/
	
	$service_usage_cdr = New service_usage_cdr;

	$service_usage_cdr->id_service_customer = $id_service_customer;

	$service_usage_cdr->load_data_service();
	$service_usage_cdr->load_data_ddi();


	/*
		Fetch bill groups
	*/

	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id, billgroup_name FROM `cdr_rate_billgroups`";
	$sql_obj->execute();

	$sql_obj->fetch_array();


	$billgroups = array();
	
	foreach ($sql_obj->data as $data)
	{
		$billgroups[ $data["id"] ] = $data["billgroup_name"];
	}



	/*
		We want to run through and match any calls that are charged as nationals but actually locals.

		Fetch all call rates which are from 64X to 64X and tagged as national calls 
	*/

	$obj_calls_sql		= New sql_query;
	$obj_calls_sql->string	= "SELECT date, price, usage1 as src, usage2 as dst, usage3 as billsec, billgroup FROM service_usage_records WHERE id_service_customer='$id_service_customer' ORDER BY date";
	$obj_calls_sql->execute();

	if (!$obj_calls_sql->num_rows())
	{
		die("No rows returned, is this a valid service to be checking?\n");
	}

	$obj_calls_sql->fetch_array();

	$refund_total = 0;

	foreach ($obj_calls_sql->data as $data_cdr)
	{
		$charges = $obj_cdr_rate_table->calculate_charges($data_cdr["billsec"], $data_cdr["src"], $data_cdr["dst"], $service_usage_cdr->data_local[ $data_cdr["src"] ], $service_usage_cdr->data_ddi);

		if ($data_cdr["billgroup"] != $charges["billgroup"])
		{
			// changed billgroup
			$charges["price"] = round($charges["price"], 2);

			// handle refund
			$refund		= $data_cdr["price"] - $charges["price"];
			$refund_total	= $refund_total + $refund;

			// write output
			log_write("debug", "repair", "Call on ". $data_cdr["date"] ." from ". $data_cdr["src"] ." to ". $data_cdr["dst"] ." for ". $data_cdr["billsec"] ." had cost of ". $data_cdr["price"] ." now has cost of ". $charges["price"] .", old billgroup of ". $data_cdr["billgroup"] ." new billgroup of ". $charges["billgroup"] ."\n");

			fwrite($handle_file_csv, '"'.$data_cdr["date"] .'";"'.$data_cdr["src"] .'";"'.$data_cdr["dst"] .'";"'.$data_cdr["billsec"] .'";"'.$data_cdr["price"] .'";"'. $charges["price"] .'";"'. $refund .'";"'. $billgroups[ $data_cdr["billgroup"] ] .'";"'. $billgroups[ $charges["billgroup"] ].'";'. "\n");
		}
	}

	// total refund
	fwrite($handle_file_csv, '"";"";"";"";"";"";"'. $refund_total .'";"";"";'. "\n");


	// close file
	fclose($handle_file_csv);

} // end of page_execute()


?>
