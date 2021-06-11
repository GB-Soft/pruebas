<?php

define("_API_URL_", "https://carrefoures-prod.mirakl.net/api");

require_once(dirname(__FILE__) . '/lib/vendor/autoload.php');

// Client
use Mirakl\MMP\Shop\Client\ShopApiClient;

//Ofertas
use Mirakl\MMP\Shop\Request\Offer\GetOffersRequest;
use Mirakl\MMP\Shop\Request\Offer\UpdateOffersRequest;
use Mirakl\Core\Domain\Collection\MiraklCollection;
use Mirakl\MMP\OperatorShop\Domain\Offer\UpdateOffer;
use Mirakl\MMP\OperatorShop\Domain\Collection\Offer\UpdateOfferCollection;


$apiKey = '4f03e9d8-d4db-444d-a679-5da94ed74eba'; //tuyson
$shopID = 3234;

$api = new ShopApiClient(_API_URL_, $apiKey);
$request = new GetOffersRequest($shopID);
$request->setSku('NovaDecoLitleBL');
$result = $api->getOffers($request);

$offer = $result->current();



$api = new ShopApiClient(_API_URL_, $apiKey, $shopID);


	$offer_array = array(
			'product_id' => '7427127368609',
			'product_id_type' => 'EAN',
			'shop_sku' => 'ENARANJA_2,00X1,80',
			'quantity' => 20,
			'price' => 50,
			'discount-price' => 35,
			'state_code' => 11,
			'update_delete' => 'update'
		);
	
	$offer_array = array(
		"shop_sku" => "ENARANJA_2,00X1,80",
        "product_id" => "7427127368609",
        "product_id_type" => "EAN",
        "internal_description" => "",
        "price" => "45.00",
        "price_additional_info" => "",
        "quantity" => 100,
        "min_quantity_alert" => "",
        'state_code' => 11,
        "available_start_date" => "",
        "available_end_date" => "",
        "logistic_class" => "",
        "favorite_rank" => "",
        "special_price" => "40.00",
        "discount_start_date" => "2018-03-01",
        "discount_end_date" => "2028-03-01",
        "discount_ranges" => "",
        "leadtime_to_ship" => "",
        "allow_quote_requests" => "",
        "update_delete" => "update",
        "price_ranges" => "10|32.00,20|25.00,5|36.00",
        "product_tax_code" => "",
        "min_order_quantity" => "1",
        "max_order_quantity" => "10000",
        "package_quantity" => "1"
	);


$request = new UpdateOffersRequest();
$request->setOffers(new UpdateOfferCollection(array($offer_array)));
try{
$result = $api->updateOffers($request);
}
catch(Exception $ex)
{
	echo $ex->getMessage();
}
var_dump($result);

?>