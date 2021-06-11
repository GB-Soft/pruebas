<?php


require_once(dirname(__DIR__, 1) . '/amazonBaseApi.php');


class Product extends amazonBaseApi
{	

	public function __construct($merchant_id  ,$mws_auth_token , $marketplaces_ids = array())
    {
        parent::__construct($merchant_id,$mws_auth_token,$marketplaces_ids);
    }

	/**
     *  Obitene ASIM de un SKU y un Marketplace
     *
     * @param   string  	$sku
     * @return  Array
     */
	public function get_product_ASIN_by_SKU($sku)
	{
		self::add_class_amazon('MarketplaceWebServiceProducts');
		
		$serviceUrl = "https://mws-eu.amazonservices.com/Products/2011-10-01";

		$config = array (
			'ServiceURL' => $serviceUrl,
			'ProxyHost' => null,
			'ProxyPort' => -1,
			'ProxyUsername' => null,
			'ProxyPassword' => null,
			'MaxErrorRetry' => 3,
		);
		
		
		$service = new MarketplaceWebServiceProducts_Client(
			AWS_ACCESS_KEY_ID,
			AWS_SECRET_ACCESS_KEY,
			APPLICATION_NAME,
			APPLICATION_VERSION,
		$config);
		
		
		//Utilizamos la función de obtener precio actual por SKU
		$request = new MarketplaceWebServiceProducts_Model_GetMyPriceForSKURequest();
		$request->setSellerId($this->merchant_id);
		$request->setMarketplaceId($this->marketplaces_ids);
		$request->setMWSAuthToken($this->mws_auth_token);
		$sku_list = new MarketplaceWebServiceProducts_Model_SellerSKUListType();
		$sku_list->setSellerSKU(array($sku));
		$request->setSellerSKUList($sku_list);
		
		try{
			$response = $service->GetMyPriceForSKU($request);
			$asin = $response->getGetMyPriceForSKUResult()[0]->getProduct()->getIdentifiers()->getMarketplaceASIN()->getASIN();
			return array("status" => "200","message" => "OK", "data" => $asin);
		}
		catch(Exception $ex)
		{
			return array("status" => "300","message" => "Error", "data" => "No es posible obtener los datos del producto. ".$e->getMessage());
		}
			
	}
	
}

?>