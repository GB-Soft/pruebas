<?php


require_once(dirname(__DIR__, 1) . '/amazonBaseApi.php');

/*
identificador del vendedor --> AMD7QHI530HTM
identificador de la web --> A1RKKUPIHCS9HS
authMws ---> amzn.mws.48b9cd9d-4665-c98b-973c-7682b3c3d04e
*/

class Order extends amazonBaseApi
{	

	public function __construct($merchant_id  ,$mws_auth_token , $marketplaces_ids = array())
    {
        parent::__construct($merchant_id,$mws_auth_token,$marketplaces_ids);
    }

	/**
     *  Obtiene los pedidos entre fechas
     *
     * @param   string  	$fecha_desde
     * @return  Array
     */
	public function get_orders($fecha_desde, $fecha_update_desde = null)
	{
		try{
            self::add_class_amazon('MarketplaceWebServiceOrders');
		    //self::add_class_amazon('MarketplaceWebServiceOrders');
			
			$serviceUrl = "https://mws-eu.amazonservices.com/Orders/2013-09-01";

			$config = array (
				'ServiceURL' => $serviceUrl,
				'ProxyHost' => null,
				'ProxyPort' => -1,
				'ProxyUsername' => null,
				'ProxyPassword' => null,
				'MaxErrorRetry' => 3,
			);
			
			$service = new MarketplaceWebServiceOrders_Client(
				AWS_ACCESS_KEY_ID,
				AWS_SECRET_ACCESS_KEY,
				APPLICATION_NAME,
				APPLICATION_VERSION,
			$config);



			$request = new MarketplaceWebServiceOrders_Model_ListOrdersRequest();
			$request->setSellerId($this->merchant_id);
			$request->setMarketplaceId($this->marketplaces_ids);
			$request->setMWSAuthToken($this->mws_auth_token);
			if(!is_null($fecha_update_desde)) //no se puede pasar una fecha de creación (Created After) junto con fecha de actualización (Last Updated After)
			{
				$updatedAfter = new DateTime($fecha_update_desde, new DateTimeZone('UTC'));
				$request->setLastUpdatedAfter($updatedAfter->format('c'));
			}
			else
			{
				$createdAfter = new DateTime($fecha_desde, new DateTimeZone('UTC'));
				$request->setCreatedAfter($createdAfter->format('c'));
			}
			
			$response = $service->ListOrders($request);


			//Cargamos los primeros pedidos
			$result = array();
			self::getArrayOrders($result, $response->getListOrdersResult()->getOrders());
			
			//El resto de pedidos (van de 100 en 100)
			$next_token = $response->getListOrdersResult()->getNextToken();
			if($response->getListOrdersResult()->isSetNextToken())
			{
				do
				{
					$request = new MarketplaceWebServiceOrders_Model_ListOrdersByNextTokenRequest();
					$request->setSellerId($this->merchant_id);
					//$request->setMarketplaceId($this->marketplaces_ids);
					$request->setMWSAuthToken($this->mws_auth_token);
					$request->setNextToken($next_token);
					$response = $service->ListOrdersByNextToken($request);
					self::getArrayOrders($result, $response->getListOrdersByNextTokenResult()->getOrders());
					$next_token = $response->getListOrdersByNextTokenResult()->getNextToken();
				}
				while ($response->getListOrdersByNextTokenResult()->isSetNextToken());
			}
			
			return array("status" => "200","message" => "OK", "data" => $result);
		}
		catch(Exception $e)
		{
			return array("status" => "300","message" => "Error", "data" => "No es posible obtener los pedidos. ".$e->getMessage());
		}
	
		//return self::invokeListOrders($service, $request);
	}
	
	function getArrayOrders(&$array, $orders, $get_lines = true)
	{
		foreach($orders as $order)
		{
			$ord = array();
			$ord['numero'] = $order->getAmazonOrderId();
			$ord['estado'] =  $order->getOrderStatus();
			$ord['canal'] =  $order->getFulfillmentChannel();
			$ord['plataforma'] =  $order->getSalesChannel();
			//$array[]['nivel_servicio_envio'] =  $order->getShipServiceLevel();
			//puede ser que no tenga cantidad total
			if($order->isSetOrderTotal())
				$ord['total_pedido'] =  array("total" => $order->getOrderTotal()->getAmount(), "moneda" => $order->getOrderTotal()->getCurrencyCode());
			$ord['n_articulos_enviados'] = $order->getNumberOfItemsShipped();	
			$ord['n_articulos_pendientes'] = $order->getNumberOfItemsUnshipped();
			if(count($aux = $order->getPaymentMethodDetails())>0)
				$ord[]['metodo_pago'] = $aux[0];
			$ord['email'] = $order->getBuyerEmail();	
			$ord['fecha_pedido'] = $order->isSetPurchaseDate()?date("Y-m-d H:i:s",strtotime($order->getPurchaseDate())):"";
			$ord['fecha_limite_envio'] = $order->isSetLatestShipDate()?date("Y-m-d H:i:s",strtotime($order->getLatestShipDate())):"";
			$ord['fecha_limite_entrega'] = $order->isSetLatestDeliveryDate()?date("Y-m-d H:i:s",strtotime($order->getLatestDeliveryDate())):"";
			$ord['pedido_empresa'] = $order->getIsBusinessOrder()=='true'?1:0; //no devuelve un bool devuelve un string...
			//$ord['prime'] = $order->isIsPrime(); //esto siempre devuelve true, no se para que es
			$ord['prime'] = $order->getFulfillmentChannel()=="AFN"?1:0;
			
			//Esperar permisos para estoooo
			//$order->getBuyerName()
			$ord['cliente'] = "";
			
			//Ver si es necesario
			//$order->getShipmentServiceLevelCategory();
			
			//el envío puede ser que aún no lo tenga (FALTA QUE TE DEN PERMISO PARA EL NOMBRE)
			$ord['direccion_envio'] = "";
			if($order->isSetShippingAddress())
			{
				if($order->getShippingAddress()->isSetName())
					$ord['direccion_envio'] .= $order->getShippingAddress()->getName()."<br>";
				if($order->getShippingAddress()->isSetAddressLine1())
					$ord['direccion_envio'] .= $order->getShippingAddress()->getAddressLine1()."<br>";
				if($order->getShippingAddress()->isSetAddressLine2())
					$ord['direccion_envio'] .= $order->getShippingAddress()->getAddressLine2()."<br>";
				if($order->getShippingAddress()->isSetAddressLine3())
					$ord['direccion_envio'] .= $order->getShippingAddress()->getAddressLine3()."<br>";
				if($order->getShippingAddress()->isSetCity())
					$ord['direccion_envio'] .= $order->getShippingAddress()->getCity()."<br>";
				if($order->getShippingAddress()->isSetCounty())
					$ord['direccion_envio'] .= $order->getShippingAddress()->getCounty()."<br>";
				if($order->getShippingAddress()->isSetDistrict())
					$ord['direccion_envio'] .= $order->getShippingAddress()->getDistrict()."<br>";
				if($order->getShippingAddress()->isSetStateOrRegion())
					$ord['direccion_envio'] .= $order->getShippingAddress()->getStateOrRegion()."<br>";
				if($order->getShippingAddress()->isSetMunicipality())
					$ord['direccion_envio'] .= $order->getShippingAddress()->getMunicipality()."<br>";
				if($order->getShippingAddress()->isSetPhone())
					$ord['direccion_envio'] .= $order->getShippingAddress()->getPhone()."<br>";
			}
			
			if($get_lines)
			{
				$order_lines = self::get_lines_order($order->getAmazonOrderId());
				if($order_lines['status']==200)
					$ord['lineas'] = $order_lines['data'];
			}
			
			$array[] = $ord;
		}
	}
	
	/**
     *  Obtiene las líneas detalle de un pedido
     *
     * @param   string  	$amz_order_id
     * @return  Array
     */
	public function get_lines_order($amz_order_id)
	{
		try{
			self::add_class_amazon('MarketplaceWebServiceOrders');
			
			$serviceUrl = "https://mws-eu.amazonservices.com/Orders/2013-09-01";
			
			$config = array (
				'ServiceURL' => $serviceUrl,
				'ProxyHost' => null,
				'ProxyPort' => -1,
				'ProxyUsername' => null,
				'ProxyPassword' => null,
				'MaxErrorRetry' => 3,
			);
			
			$service = new MarketplaceWebServiceOrders_Client(
				AWS_ACCESS_KEY_ID,
				AWS_SECRET_ACCESS_KEY,
				APPLICATION_NAME,
				APPLICATION_VERSION,
			$config);
		
			
			$request = new MarketplaceWebServiceOrders_Model_ListOrderItemsRequest();
			$request->setSellerId($this->merchant_id);
			//$request->setMarketplaceId($this->marketplaces_ids);
			$request->setMWSAuthToken($this->mws_auth_token);
			$request->setAmazonOrderId($amz_order_id);
			
			$response = $service->ListOrderItems($request);
			
			//Cargamos las primeras lineas
			$result = array();
			self::getArrayLinesOrder($result, $response->getListOrderItemsResult()->getOrderItems());
			
			//El resto de pedidos (van de 100 en 100)
			if($response->getListOrderItemsResult()->isSetNextToken())
			{
				$next_token = $response->getListOrderItemsResult()->getNextToken();
				do
				{
					$request = new MarketplaceWebServiceOrders_Model_ListOrderItemsByNextTokenRequest();
					$request->setSellerId($this->merchant_id);
					//$request->setMarketplaceId($this->marketplaces_ids);
					$request->setMWSAuthToken($this->mws_auth_token);
					$request->setNextToken($next_token);
					$response = $service->ListOrderItemsByNextToken($request);
					self::getArrayLinesOrder($result, $response->getListOrderItemsByNextTokenResult()->getOrderItems());
					$next_token = $response->getListOrderItemsByNextTokenResult()->getNextToken();
				}
				while ($response->getListOrderItemsByNextTokenResult()->isSetNextToken());
			}
			
			return array("status" => "200","message" => "OK", "data" => $result);
		}
		catch(Exception $e)
		{
			return array("status" => "300","message" => "Error", "data" => "No es posible obtener las líneas de pedido. ".$e->getMessage());
		}
	}
	
	function getArrayLinesOrder(&$array, $lines)
	{
		foreach($lines as $line)
		{
			$ln = array();
			$ln['asin'] = $line->getASIN();
			$ln['sku'] = $line->getSellerSKU();
			$ln['id_linea'] = $line->getOrderItemId();
			$ln['titulo_producto'] = $line->getTitle();
			$ln['cantidad'] = $line->getQuantityOrdered();
			$ln['cantidad_enviada'] = $line->getQuantityShipped();
			if($line->isSetItemPrice())
				$ln['precio_linea'] =  array("total" => $line->getItemPrice()->getAmount(), "moneda" => $line->getItemPrice()->getCurrencyCode());
			//if($line->isSetShippingPrice())
				//revisar función $line->getShippingPrice() cuando alguien tenga precio envío
			if($line->isSetItemTax())
				$ln['iva_linea'] =  array("total" => $line->getItemTax()->getAmount(), "moneda" => $line->getItemTax()->getCurrencyCode());
			//Esto quizá es para la personalización
			//$line->getBuyerCustomizedInfo()
			$array[] = $ln;
		}
	}
	
	/**
     *  Obtiene un pedido por su numero
     *
     * @param   string  	$order_number
     * @return  Array
     */
	public function get_order($order_number)
	{
		try{
			self::add_class_amazon('MarketplaceWebServiceOrders');
			
			$serviceUrl = "https://mws-eu.amazonservices.com/Orders/2013-09-01";

			
			$config = array (
				'ServiceURL' => $serviceUrl,
				'ProxyHost' => null,
				'ProxyPort' => -1,
				'ProxyUsername' => null,
				'ProxyPassword' => null,
				'MaxErrorRetry' => 3,
			);
			
			$service = new MarketplaceWebServiceOrders_Client(
				AWS_ACCESS_KEY_ID,
				AWS_SECRET_ACCESS_KEY,
				APPLICATION_NAME,
				APPLICATION_VERSION,
			$config);
		
			$request = new MarketplaceWebServiceOrders_Model_GetOrderRequest();
			$request->setSellerId($this->merchant_id);
			$request->setMWSAuthToken($this->mws_auth_token);
			$request->setAmazonOrderId($order_number);
			
			$response = $service->GetOrder($request);
			
			$result = array();
			self::getArrayOrders($result, $response->getGetOrderResult()->getOrders());
			
			return array("status" => "200","message" => "OK", "data" => $result[0]);
		}
		catch(Exception $e)
		{
			return array("status" => "300","message" => "Error", "data" => "No es posible obtener los pedidos. ".$e->getMessage());
		}
	}
	
}

?>