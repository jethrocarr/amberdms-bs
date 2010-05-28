<?php
 
	require("../../include/config.php");
	require("../../include/amberphplib/main.php");
	require("../../include/products/inc_products.php");
	
	$prod_id = @security_script_input_predefined("int", $_GET['id']);
	
	$obj_product = New product;
	$obj_product->id = $prod_id;
	$obj_product->load_data();
	
	echo json_encode($obj_product->data);

	exit(0);
?>