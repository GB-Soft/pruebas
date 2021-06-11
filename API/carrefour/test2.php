<?php
include("carrefour.php");

$apiKey = '4f03e9d8-d4db-444d-a679-5da94ed74eba'; //tuyson
$shopID = 3234;

$api = new Carrefour_API($apiKey);
//$result = $api->get_offer('NovaDecoLitleBL');

$data['ean'] = '8433268000277';
$data['offer_sku'] = 'NovaDecoLitleBL';

$data['quantity'] = 10;
$data['update_delete'] = 'update';
$data['origin_price'] = 500;
$data['offer_price'] = 350;
$data['offer_description'] = '';

$result = $api->update_offers($data);

var_dump($result);
?>