<?php

//include("xmlProduct.php");
include("../feed/feed.php");
include("../products/product.php");

//EGO COMMERRCE
$fakemwstoken = 'amzn.mws.48b9cd9d-4665-c98b-973c-7682b3c3d04e';
$fakemerchantId = 'AMD7QHI530HTM';


//CLIENTE REAL
$realmwstoken = 'amzn.mws.00bbda29-c7d2-455c-d663-204e702d0722';
$realmerchantId = 'A2CSD15YB3U1HH';

$marketplaceIdArray = array("Id" => array("A1RKKUPIHCS9HS"));
$marketplaceId = "A1RKKUPIHCS9HS";

/* prueba get product by SKU */
/*$product = new Product($realmerchantId,$realmwstoken,$marketplaceId);
$res = $product->get_product_ASIN_by_SKU("OasiKuarzoBl");

var_dump($res);

exit;*/
/* FIn prueba get product by SKU*/


//$path = "./xml/baby.json";

/*PRUEBA FEED RESULT */
			/*$feed = new Feed($realmerchantId,$realmwstoken);
			$result = $feed->getFeedSubmisionResult('168999018768'); //169604018771
var_dump($result);
exit;*/
/* FIN PRUEBA FEED RESULT */


/*PRUEBA DE py*/

$output = shell_exec("python3 main.py files/custom/Autre_Outilsmultifonctionetaccessoires.xlsm 2>&1");

var_dump($output);

exit;
/*FIN PRUEBA PY */


/* PRUEBA GERARDO */

$marketplaceIdArray = array("Id" => array("A1RKKUPIHCS9HS"));


//$file_contents = stream_get_contents("feed.csv");
$file_contents = file_get_contents("test.csv");

$feed = new Feed($realmerchantId,$realmwstoken,$marketplaceIdArray);
$result = $feed->submitFeed($file_contents,'_POST_FLAT_FILE_LISTINGS_DATA_');

var_dump($result);


//$feedHandle = @fopen('feed.csv', 'r');
//fwrite($feedHandle, $feed);
//rewind($feedHandle);

//echo stream_get_contents($feedHandle);

/*require_once(dirname(__DIR__, 1) . '/amazonBaseApi.php');

amazonBaseApi::add_class_amazon('MarketplaceWebService');
amazonBaseApi::add_class_amazon('MarketplaceWebServiceProducts_Client');

$serviceUrl = "https://mws-eu.amazonservices.com/Products/2013-09-01";

$config = array (
	'ServiceURL' => $serviceUrl,
	'ProxyHost' => null,
	'ProxyPort' => -1,
	'ProxyUsername' => null,
	'ProxyPassword' => null,
	'MaxErrorRetry' => 3,
);
			
$service = new MarketplaceWebServiceproducts_Client(
	AWS_ACCESS_KEY_ID,
	AWS_SECRET_ACCESS_KEY,
	APPLICATION_NAME,
	APPLICATION_VERSION,
$config);

echo "hola";
exit;


$request = new MarketplaceWebService_Model_SubmitFeedRequest();
$request->setMerchant(MERCHANT_ID);
$request->setMarketplaceIdList($marketplaceIdArray);
$request->setFeedType('_POST_FLAT_FILE_INVLOADER_DATA_'); //_POST_FLAT_FILE_LISTINGS_DATA_ 
$request->setContentMd5(base64_encode(md5($file_contents, true)));
$request->setFeedContent($file_contents);
//$result = $request->invokeSubmitFeed($service, $request );


var_dump($result);*/




echo "hola";




exit;



$xmlProduct = new XmlProduct($realmerchantId,$realmwstoken);

/*_____________________________________________________________________________________*/

//PREPARAMOS NUESTRO DOCUMENTO PARA ENVIAR AL SERVIDOR

$pathProduct = realpath ("./xml/productSample.json");
$pathBeauty = realpath ("./xml/beautySample.json");


$data = $xmlProduct->convertJsonToArray($pathProduct);
$product = $xmlProduct->getProduct($data);

 //este seria nuestro xml ya formado listo para ser enviado a la api

$beauty = $xmlProduct->convertJsonToArray($pathBeauty);
$beauty = $xmlProduct->covnertType($beauty,'Beauty');


$feed  = $xmlProduct->fusionProductCategory($product,$beauty);
print_r($feed);

//$product = $xmlProduct->mountSchema($product);

/*_____________________________________________________________________________________*/

$feed = new Feed($realmerchantId,$realmwstoken);

//DESCOMENTAR LAS SIGUIENTES LINEAS PARA ENVIAR UN PRODUCTO

//$ubmitId = $feed->submitFeed($product,'_POST_PRODUCT_DATA_');
//$result = $feed->getFeedSubmisionList('iwfhrwig');
//$result = $feed->getFeedSubmisionResult('iwfhrwig');






