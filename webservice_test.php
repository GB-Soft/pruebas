<?php
	include ("inc/config.php");
	include("functions.php");
	
	
	include ('API/amazon/amazon.php');
	$api = new Amazon_API('A2CSD15YB3U1HH' ,'amzn.mws.00bbda29-c7d2-455c-d663-204e702d0722', array('A1RKKUPIHCS9HS'));
	
	
	//$fecha_desde = date("d-m-Y",strtotime($_POST['fecha_desde']));
	
	$result = $api->get_orders('2021-01-23', '2020-02-22');
	
	var_dump($result);
	exit;
	
?>