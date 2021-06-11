<?php
define("_API_URL_", "https://carrefoures-prod.mirakl.net/api");

require_once(dirname(__FILE__) . '/lib/vendor/autoload.php');
// Client
use Mirakl\MMP\Shop\Client\ShopApiClient;
//UserTypes
use Mirakl\MMP\Common\Domain\UserType;
//Categorias
use Mirakl\MCI\Shop\Request\Hierarchy\GetHierarchiesRequest;
//Razones de Cancelación, Reembolso...
use Mirakl\MMP\Shop\Request\Reason\GetTypeReasonsRequest;
use Mirakl\MMP\Common\Domain\Reason\ReasonType;
//Ofertas
use Mirakl\MMP\Shop\Request\Offer\GetOffersRequest;
use Mirakl\MMP\Shop\Request\Offer\UpdateOffersRequest;
use Mirakl\MMP\OperatorShop\Domain\Collection\Offer\UpdateOfferCollection;
use Mirakl\MMP\Common\Domain;
//Productos
use Mirakl\MMP\Shop\Request\Product\GetProductsRequest;
use Mirakl\MMP\Common\Domain\Product\Offer\ProductReference;
use Mirakl\MCI\Shop\Request\Product\ProductImportRequest;
use Mirakl\MCI\Shop\Request\Product\ProductImportStatusRequest;
use Mirakl\MCI\Shop\Request\Product\DownloadProductImportTransformationErrorReportRequest;
//Mapeo Categorías
use Mirakl\MCI\Shop\Request\Attribute\GetAttributesRequest;
//Pedidos
use Mirakl\MMP\Shop\Request\Order\Get\GetOrdersRequest;
use Mirakl\MMP\Shop\Request\Order\Accept\AcceptOrderRequest;
use Mirakl\MMP\Common\Domain\Order\Accept\AcceptOrderLine;
//Mensajes (Pedidos)
use Mirakl\MMP\Shop\Request\Order\Message\GetOrderMessagesRequest;
use Mirakl\MMP\Shop\Request\Order\Message\CreateOrderMessageRequest;
//Envío (Pedidos)
use Mirakl\MMP\Shop\Request\Order\Ship\ShipOrderRequest;
use Mirakl\MMP\Shop\Request\Order\Tracking\UpdateOrderTrackingInfoRequest;
//Reembolso (Pedidos)
use Mirakl\MMP\Shop\Request\Order\Refund\CreateRefundRequest;
//Documentos (Pedidos)
use Mirakl\MMP\Shop\Request\Order\Document\GetOrderDocumentsRequest;
use Mirakl\Core\Domain\Collection\DocumentCollection;
use Mirakl\Core\Domain\Document;
use Mirakl\MMP\Shop\Request\Order\Document\UploadOrdersDocumentsRequest;
use Mirakl\MMP\Shop\Request\Order\Document\DeleteOrderDocumentRequest;

class Carrefour_API
{	
	
	public $apiUrl;
	public $apiKey;
	public $shopID;

	public function __construct($key,$url = null, $shopid = null)
    {
		if(is_null($url) || $url == "")
       		$this->apiUrl = _API_URL_;
		else
			$this->apiUrl = $url;
		$this->apiKey = $key;
		$this->shopID = $shopid;
		
    }
	
	/**
     *  Obtiene el shop id
     * @return  string
     */
	function get_shop_id()
	{
		$api = new ShopApiClient($this->apiUrl, $this->apiKey);
		return $api->getAccount()->getId();
	}
	
	/**
     *  Obtiene listado de razones del market (cancelación, Reembolso)
     * @return  array (code => label)
     */
	public function get_reasons($reasonType) //ReasonType: Mirakl\MMP\Common\Domain\Reason
	{
		try
		{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey, $this->shopID);
			$request = new GetTypeReasonsRequest($reasonType);
			$result = $api->getTypeReasons($request);
			$reasons = array();
			foreach($result as $r)
				$reasons[$r->getCode()] = $r->getLabel();
			return $reasons;
		}
		catch (Exception $e) {
			return $e;
		}
	}
	
	/**
     *  Obtiene listado de razones del market para cencelación
     * @return  array (code => label)
     */
	public function get_reasons_refund()
	{
		try
		{
			return self::get_reasons(ReasonType::REFUND);
		}
		catch (Exception $e) {
			return $e;
		}
	}
	
	/**
     *  Obtiene listado de categorias
     * @return  array (code => array)
     */
	public function get_categories()
	{
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey, $this->shopID);
			$request = new GetHierarchiesRequest();
			//$request->setHierarchyCode('HIERARCHY_CODE'); // Optional
			//$request->setMaxLevel(2); // Optional (all children by default)
			$categories = $api->getHierarchies($request);
			
			$arr_cat = array();
			foreach($categories as $cat)
			{
				$arr_cat[$cat->getCode()] = array(
					"level" => $cat->getLevel(),
					"label" => $cat->getLabel(),
					"parent_code" => $cat->getParentCode()
				);
			}
			return array("status" => "OK","data" => $arr_cat);
		}
		catch (Exception $e) {
			return array("status" => "Error","message" => "No se pudieron obtener las categorías");
		}
	}
	
	
	/**
     *  Obtiene los pedidos entre fechas
     *
     * @param   string  	$fecha_desde
     * @return  Array
     */
	public function get_orders($fecha_desde)
	{
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey);
  			$request = new GetOrdersRequest();
			
			$orders = array();
			$offset = 0;
			while (true) {
				$request = new GetOrdersRequest();
       			$request->setOffset($offset)
            	->setMax(10)
            	->setSortBy('dateCreated')
            	->setDir('DESC')
				->setStartDate($fecha_desde);
      			/*->setEndDate($fecha_hasta);*/
            	
				$result = $api->getOrders($request);
            	$orders = array_merge($orders, $result->getItems());
				
            	if (!$result->count() || count($orders) >= $result->getTotalCount()) {
					break;
            	}
            	$offset += 10;
			}
			
			//Convert to array
			$result = array();
			$i = 0;
			
			foreach($orders as $order)
			{
				$result[$i]['estado'] = $order->getStatus()->getState();	
				$result[$i]['cliente'] = $order->getCustomer()->getFirstname()." ".$order->getCustomer()->getLastname();
				$result[$i]['numero'] = $order->getId();
				$result[$i]['fecha_pedido'] = date_format($order->getCreatedDate(),"Y-m-d H:i:s");
				$result[$i]['fecha_limite_envio'] = date_format($order->getShippingDeadline(),"Y-m-d H:i:s");
				
				if ($result[$i]['estado'] != "CANCELED" && $result[$i]['estado'] != "REFUSED") //los cancelados no permiten obtener información del envío y facturación
				{
					//si el pedido aún no ha sido aceptado no se puede obtener la dirección de envío ni facturación:
					if($result[$i]['estado'] == "WAITING_ACCEPTANCE")
					{
						$direccion_envio = $direccion_facturacion = "Esta información estará disponible una vez se confirme el débito.";
					}
					else
					{
						$direccion_envio = "";
						$direccion_envio .= $order->getCustomer()->getShippingAddress()->getStreet1() . ($order->getCustomer()->getShippingAddress()->getStreet1()!=""?"<br>":"");
						$direccion_envio .= $order->getCustomer()->getShippingAddress()->getStreet2() . ($order->getCustomer()->getShippingAddress()->getStreet2()!=""?"<br>":"");
						$direccion_envio .= $order->getCustomer()->getShippingAddress()->getZipCode()." ".$order->getCustomer()->getShippingAddress()->getCity() . ($order->getCustomer()->getShippingAddress()->getCity()!=""?"<br>":"");
						$direccion_envio .= $order->getCustomer()->getShippingAddress()->getState() . ($order->getCustomer()->getShippingAddress()->getState()!=""?"<br>":"");
						$direccion_envio .= $order->getCustomer()->getShippingAddress()->getCountry() . ($order->getCustomer()->getShippingAddress()->getCountry()!=""?"<br>":"");
						$direccion_envio .= $order->getCustomer()->getShippingAddress()->getPhone() . ($order->getCustomer()->getShippingAddress()->getPhone()!=""?"<br>":"");
						$direccion_envio .= $order->getCustomer()->getShippingAddress()->getPhoneSecondary() . ($order->getCustomer()->getShippingAddress()->getPhoneSecondary()!=""?"<br>":"");
					
						$direccion_facturacion = "";
						$direccion_facturacion .= $order->getCustomer()->getBillingAddress()->getStreet1() . ($order->getCustomer()->getBillingAddress()->getStreet1()!=""?"<br>":"");
						$direccion_facturacion .= $order->getCustomer()->getBillingAddress()->getStreet2() . ($order->getCustomer()->getBillingAddress()->getStreet2()!=""?"<br>":"");
						$direccion_facturacion .= $order->getCustomer()->getBillingAddress()->getZipCode()." ".$order->getCustomer()->getBillingAddress()->getCity() . ($order->getCustomer()->getBillingAddress()->getCity()!=""?"<br>":"");
						$direccion_facturacion .= $order->getCustomer()->getBillingAddress()->getState() . ($order->getCustomer()->getBillingAddress()->getState()!=""?"<br>":"");
						$direccion_facturacion .= $order->getCustomer()->getBillingAddress()->getCountry() . ($order->getCustomer()->getBillingAddress()->getCountry()!=""?"<br>":"");
						$direccion_facturacion .= $order->getCustomer()->getBillingAddress()->getPhone() . ($order->getCustomer()->getBillingAddress()->getPhone()!=""?"<br>":"");
						$direccion_facturacion .= $order->getCustomer()->getBillingAddress()->getPhoneSecondary() . ($order->getCustomer()->getBillingAddress()->getPhoneSecondary()!=""?"<br>":"");
					}
					$result[$i]['direccion_envio'] = $direccion_envio;
					$result[$i]['direccion_facturacion'] = $direccion_facturacion;
				}
				$result[$i]['metodo_envio'] = $order->getShipping()->getType()->getCode();
				$result[$i]['total_pedido'] = $order->getTotalPrice();
				
				//Tracking
				$result[$i]['numero_seguimiento'] = $order->getShipping()->getTrackingNumber();
				$result[$i]['codigo_transportista'] = $order->getShipping()->getCarrierCode();
				$result[$i]['url_seguimiento'] = $order->getShipping()->getTrackingUrl();
				
				$result[$i]['lineas'] = array();
				foreach ($order->getOrderLines() as $line)
				{
					$linea = array();
					$linea['linea'] = $line->getIndex();
					$linea['numero_linea'] = $line->getId();
					$linea['estado'] = $line->getStatus()->getState();
					$linea['sku'] = $line->getOffer()->getSku();
					$linea['titulo'] = $line->getOffer()->getProduct()->getTitle();
					if($line->getProductMedia()->get(1))
						$linea['imagen'] = $line->getProductMedia()->get(1)->getMediaUrl(); //1 is medium image
					$linea['cantidad'] = $line->getQuantity();
					$linea['precio_unidad'] = $line->getOffer()->getPrice();
					$linea['precio_envio'] = $line->getShippingPrice();
					$linea['total_linea'] = $line->getTotalPrice();
					if(count($refunds = $line->getRefunds())>0)
					{
						//TODO: If you have more than one partial refund, add the total of all refunds
						foreach ($refunds as $refund)
						{
							$linea['unidades_reembolso'] = $refund->getQuantity();
							$linea['importe_reembolso'] = $refund->getAmount();
							$linea['codigo_motivo_reembolso'] = $refund->getReasonCode();
						}
					}
					array_push($result[$i]['lineas'], $linea);
				}
				$i++;
			}
		}
		catch (Exception $e) {
			return $e;
		}
		return $result;
	}
	
	/**
     *  Obtiene un pedido a través su número
     *
     * @param   string  	$order_number
     * @return  Array
     */
	public function get_order_by_number($order_number)
	{
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey);
			$request = new GetOrdersRequest();
			$request->setOrderIds(array($order_number));
			$orders = $api->getOrders($request);
			$order = $orders[0];
			
			
			$result['estado'] = $order->getStatus()->getState();	
			$result['cliente'] = $order->getCustomer()->getFirstname()." ".$order->getCustomer()->getLastname();
			$result['numero'] = $order->getId();
			$result['fecha_pedido'] = date_format($order->getCreatedDate(),"Y-m-d H:i:s");
			$result['fecha_limite_envio'] = date_format($order->getShippingDeadline(),"Y-m-d H:i:s");
			
			if ($result['estado'] != "CANCELED" && $result['estado'] != "REFUSED" ) //los cancelados y rechazados no permiten obtener información del envío y facturación
			{
				//si el pedido aún no ha sido aceptado no se puede obtener la dirección de envío ni facturación:
				if($result['estado'] == "WAITING_ACCEPTANCE")
				{
					$direccion_envio = $direccion_facturacion = "Esta información estará disponible una vez se confirme el débito.";
				}
				else
				{
					$direccion_envio = "";
					$direccion_envio .= $order->getCustomer()->getShippingAddress()->getStreet1() . ($order->getCustomer()->getShippingAddress()->getStreet1()!=""?"<br>":"");
					$direccion_envio .= $order->getCustomer()->getShippingAddress()->getStreet2() . ($order->getCustomer()->getShippingAddress()->getStreet2()!=""?"<br>":"");
					$direccion_envio .= $order->getCustomer()->getShippingAddress()->getZipCode()." ".$order->getCustomer()->getShippingAddress()->getCity() . ($order->getCustomer()->getShippingAddress()->getCity()!=""?"<br>":"");
					$direccion_envio .= $order->getCustomer()->getShippingAddress()->getState() . ($order->getCustomer()->getShippingAddress()->getState()!=""?"<br>":"");
					$direccion_envio .= $order->getCustomer()->getShippingAddress()->getCountry() . ($order->getCustomer()->getShippingAddress()->getCountry()!=""?"<br>":"");
					$direccion_envio .= $order->getCustomer()->getShippingAddress()->getPhone() . ($order->getCustomer()->getShippingAddress()->getPhone()!=""?"<br>":"");
					$direccion_envio .= $order->getCustomer()->getShippingAddress()->getPhoneSecondary() . ($order->getCustomer()->getShippingAddress()->getPhoneSecondary()!=""?"<br>":"");
				
					$direccion_facturacion = "";
					$direccion_facturacion .= $order->getCustomer()->getBillingAddress()->getStreet1() . ($order->getCustomer()->getBillingAddress()->getStreet1()!=""?"<br>":"");
					$direccion_facturacion .= $order->getCustomer()->getBillingAddress()->getStreet2() . ($order->getCustomer()->getBillingAddress()->getStreet2()!=""?"<br>":"");
					$direccion_facturacion .= $order->getCustomer()->getBillingAddress()->getZipCode()." ".$order->getCustomer()->getBillingAddress()->getCity() . ($order->getCustomer()->getBillingAddress()->getCity()!=""?"<br>":"");
					$direccion_facturacion .= $order->getCustomer()->getBillingAddress()->getState() . ($order->getCustomer()->getBillingAddress()->getState()!=""?"<br>":"");
					$direccion_facturacion .= $order->getCustomer()->getBillingAddress()->getCountry() . ($order->getCustomer()->getBillingAddress()->getCountry()!=""?"<br>":"");
					$direccion_facturacion .= $order->getCustomer()->getBillingAddress()->getPhone() . ($order->getCustomer()->getBillingAddress()->getPhone()!=""?"<br>":"");
					$direccion_facturacion .= $order->getCustomer()->getBillingAddress()->getPhoneSecondary() . ($order->getCustomer()->getBillingAddress()->getPhoneSecondary()!=""?"<br>":"");
				}
				$result['direccion_envio'] = $direccion_envio;
				$result['direccion_facturacion'] = $direccion_facturacion;
			}
			$result['metodo_envio'] = $order->getShipping()->getType()->getCode();
			$result['total_pedido'] = $order->getTotalPrice();
			
			//Tracking
			$result['numero_seguimiento'] = $order->getShipping()->getTrackingNumber();
			$result['codigo_transportista'] = $order->getShipping()->getCarrierCode();
			$result['url_seguimiento'] = $order->getShipping()->getTrackingUrl();
			
			$result['lineas'] = array();
			
			
			foreach ($order->getOrderLines() as $line)
			{
				$linea = array();
				$linea['linea'] = $line->getIndex();
				$linea['numero_linea'] = $line->getId();
				$linea['estado'] = $line->getStatus()->getState();
				$linea['sku'] = $line->getOffer()->getSku();
				$linea['titulo'] = $line->getOffer()->getProduct()->getTitle();
				if($line->getProductMedia()->get(1))
					$linea['imagen'] = $line->getProductMedia()->get(1)->getMediaUrl(); //1 is medium image
				$linea['cantidad'] = $line->getQuantity();
				$linea['precio_unidad'] = $line->getOffer()->getPrice();
				$linea['precio_envio'] = $line->getShippingPrice();
				$linea['total_linea'] = $line->getTotalPrice();
				if(count($refunds = $line->getRefunds())>0)
				{
					//TODO: If you have more than one partial refund, add the total of all refunds
					foreach ($refunds as $refund)
					{
						$linea['unidades_reembolso'] = $refund->getQuantity();
						$linea['importe_reembolso'] = $refund->getAmount();
						$linea['codigo_motivo_reembolso'] = $refund->getReasonCode();
					}
				}
				array_push($result['lineas'], $linea);
			}

			return array("status" => "OK","data" => $result);			
		}
		catch (Exception $e) {
			return array("status" => "Error","message" => "No se pudo obtener el pedido");
		}
	}
	
	/**
     *  Acepta un pedido (Cambia el estado de Wait_acceptace a Accepted)
     *
     * @param   string  	$fecha_desde
     * @return  Array
     */
	public function accept_order($id_order, $lines)
	{
		$lineas = array();
		foreach($lines as $line)
		{
			$accept_line = new AcceptOrderLine(array('id' => $line, 'accepted' => true));
			array_push($lineas, $accept_line);
		}
		$api = new ShopApiClient($this->apiUrl, $this->apiKey, $this->shopID);
		
		$request = new AcceptOrderRequest($id_order, $lineas);
		try{
  			$api->acceptOrder($request);
			return array("status" => "OK");
		}
		catch (Exception $e)
		{
			return array("status" => "Error","message" => "No es posible aceptar el pedido");
		}
	}
	
	/**
     *  Obtiene los mensajes de un pedido
     *
     * @param   string  	$order_number
     * @return  Array
     */
	public function get_order_messages($order_number)
	{
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey, $this->shopID);
			$request = new GetOrderMessagesRequest($order_number);
			$request->setPaginate(false);
			$request->setUserType(UserType::SHOP);
			$messages = $api->getOrderMessages($request);
			$result = array();
			foreach($messages as $message)
			{
				$msg = array(
					"subject" => $message->getSubject(),
					"body" => $message->getBody(),
					"date" => $message->getDateCreated()->format('Y-m-d H:i:s'),
					"user" => array("name" => $message->getUserSender()->getName(), "type" => $message->getUserSender()->getType())
				);
				if($documents = $message->getDocuments())
				{
					foreach($documents as $doc)
						$msg["documents"][] = '/shop/order/'.$order_number.'/document/'.$doc->getId();
				}
				
				array_push($result, $msg);
			}
			return array("status" => "OK","data" => $result);	
		}
		catch (Exception $e)
		{
			return array("status" => "Error","message" => "No es posible obtener los mensajes");
		}
	}
	/**
     *  Manda un mensaje a un pedido
     *
     * @param   string  	$order_number
	 * @param   string  	$asunto
	 * @param   string  	$mensaje
     * @return  Array
     */
	public function send_order_message($order_number, $asunto, $mensaje)
	{
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey, $this->shopID);
			
			$request = new CreateOrderMessageRequest($order_number, array(
				'subject'            => $asunto,
				'body'               => $mensaje,
				'to_customer'        => true,
				'to_operator'        => false,
			));
			$result = $api->createOrderMessage($request);
			return array("status" => "OK");
		}
		catch (Exception $e)
		{
			return array("status" => "Error","message" => "No es posible enviar el mensaje");
		}
	}
	
	/**
     * Obtiene los transportistas de la plataforma
     * @return  Array
     */
	public function get_carriers()
	{
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey, $this->shopID);
			$carriers = $api->getShippingCarriers();
			$result = array();
			foreach($carriers as $carrier)
			{
				$carr = array(
					"code" => $carrier->getCode(),
					"label" => $carrier->getLabel(),
					"tracking_url" => $carrier->getTrackingUrl()
				);
				array_push($result, $carr);
			}
			return array("status" => "OK","data" => $result);
		}
		catch (Exception $e)
		{
			return array("status" => "Error","message" => "No es posible obtener los transportistas");
		}
	}
	
	/**
     * Confirma el envío de un pedido
	 *
     * @param   string  	$order_number
     * @return  Array
     */
	public function ship_order($order_number)
	{
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey, $this->shopID);
			$request = new ShipOrderRequest($order_number);
 			$api->shipOrder($request);
			return array("status" => "OK");
		}
		catch (Exception $e)
		{
			return array("status" => "Error","message" => "No es posible confirmar el envío del pedido");
		}
	}
	/**
     * Actualiza el tracking Order
	 *
     * @param   string  	$order_number
	 * @param   string  	$carrier_code
	 * @param   string  	$carrier_name
	 * @param   string  	$carrier_url
	 * @param   string  	$tracking_number
     * @return  Array
     */
	public function update_tracking_order($order_number, $carrier_code, $carrier_name, $carrier_url, $tracking_number)
	{
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey, $this->shopID);
			$tracking = array(
				'carrier_code'    => (string)$carrier_code,
				'carrier_name'    => (string)$carrier_name,
				'carrier_url'     => (string)$carrier_url,
				'tracking_number' => (string)$tracking_number,
			);
			$request = new UpdateOrderTrackingInfoRequest($order_number, $tracking);
			$api->updateOrderTrackingInfo($request);
			return array("status" => "OK");
		}
		catch (Exception $e)
		{
			return array("status" => "Error","message" => "No es posible confirmar el envío del pedido");
		}
	}
	
	/**
     * Crea un reembolso por línea de pedido
	 *
     * @param   string  	$order_number
	 * @param   array 		$data associative array with keys (amount, reason_code, order_line_id, quantity, shipping_amount) per line;	
     * @return  Array
     */
	public function refund_order($order_id, $data)
	{
		try
		{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey, $this->shopID);
			$request = new CreateRefundRequest($data);
  			$result = $api->refundOrder($request);
			return array("status" => "OK");
		}
		catch (Exception $e) {
			return array("status" => "Error","message" => "No es posible realizar el abono");
		}
	}
	/**
     * Obtiene los documentos de un pedido
     * @return  Array
     */
	public function get_order_documents($order_number)
	{
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey, $this->shopID);
 			$request = new GetOrderDocumentsRequest(array($order_number));
  			$result = $api->getOrderDocuments($request);
			
			$documents = array();
			foreach($result as $doc)
			{
				$document = array(
					"id" => $doc->getId(),
					"name" => $doc->getFileName(),
					"type" => $doc->getTypeCode(),
					"date_created" => date_format($doc->getDateUploaded(),"Y-m-d H:i:s")
				);
				array_push($documents, $document);
			}
			return array("status" => "OK","data" => $documents);
		}
		catch (Exception $e)
		{
			return array("status" => "Error","message" => "No es posible obtener los documentos");
		}
	}
	/**
     * Sube un documento a pedido
	 *
     * @param   string  		$order_number
	 * @param   string 			$file_type (OTHERS, CUSTOMER_INVOICE);	
	 * @param   string 			$file_name;	
	 * @param   SplFileObject 	$file;	
     * @return  Array
     */
	public function send_order_document($order_number, $file_type, $file_name, $file)
	{
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey, $this->shopID);
			$docs = new DocumentCollection();
			$docs->add(new Document($file, $file_name, $file_type));
			$request = new UploadOrdersDocumentsRequest($docs, $order_number);
			$result = $api->uploadOrderDocuments($request);
			return array("status" => "OK","data" => $result);
		}
		catch (Exception $e)
		{
			return array("status" => "Error","message" => $e->getMessage());
		}
	}
	/**
     * Elimina un documento de un pedido
	 *
     * @param   string  		$order_number
	 * @param   string 			$document_number;	
	 * @param   SplFileObject 	$file;	
     * @return  Array
     */
	public function delete_document_order($document_number)
	{
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey, $this->shopID);  
			$request = new DeleteOrderDocumentRequest($document_number);
			$result = $api->deleteOrderDocument($request);
 			return array("status" => "OK","data" => $result);
		} 
		catch (\Exception $e) {
			return array("status" => "Error","message" => $e->getMessage());
		}	
	}
	
	/**** OFERTAS *******/
	
	/**
     * Obtiene la información de una oferta
     * @return  Array
     */
	public function get_offer($offer_sku)
	{
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey);
 			$request = new GetOffersRequest($api->getShopId());
  			$request->setSku($offer_sku);
			$result = $api->getOffers($request);
			
			$oferta = array();
			
			if(is_null($result) || empty($result) || count($result)==0)
			{
				return array("status" => "OK","data" => $oferta);
			}
			
			$offer = $result->current();
			
			$oferta = array(
				"active" => $offer->getActive(),
				"id" => $offer->getId(),
				"sku" => $offer->getSku(),
				"origin_price" => $offer->getAllPrices()->current()->getUnitOriginPrice(),
				"offer_price" => $offer->getAllPrices()->current()->getPrice(),
				"price_with_discount" => (!is_null($offer->getDiscount())?$offer->getDiscount()->getDiscountPrice():null),
				"quantity" => $offer->getQuantity(),
				"offer_description" => $offer->getDescription()
			);
			
			
			return array("status" => "OK","data" => $oferta);
		}
		catch (Exception $e)
		{
			return array("status" => "Error","message" => "No es posible obtener la oferta");
		}
	}
	
	/**
     * Actualiza una oferta
     * @return  Array
     */
	public function update_offer($ean, $sku, $origin_price, $offer_price, $quantity, $offer_description)
	{
			
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey, $this->shopID);
			$request = new UpdateOffersRequest();
			
			$offer_array = array(
					'product_id' => $ean,
					'product_id_type' => 'EAN',
					'shop_sku' => $sku,
					'quantity' => $quantity,
					'state_code' => 11, //11 = Nuevo, de momento no permitimos cambiar el estado
					'update_delete' => 'update',
				);
			
			if($origin_price>0)
			{
				$offer_array['price'] = $origin_price;
				$offer_array['discount'] = $offer_price;
			}
			else
				$offer_array['price'] = $offer_price;
			
			if ($offer_description != "")
			{
				$offer_array['description'] = $offer_description;
				$offer_array['internal_description'] = $offer_description;
			}
			
			$request->setOffers(
				$offer_array
			);
	
			$result = $api->updateOffers($request);
			
			return array("status" => "OK","data" => $result);	
		}
		catch (Exception $e) {
			return array("status" => "Error","message" => $e->getMessage());
		}
	}
	
	public function update_offers($data)
	{
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey, $this->shopID);
			$request = new UpdateOffersRequest();
			$offers = array();
			foreach($data as $d)
			{
				$offer_array = array(
						'product_id' => $d['ean'],
						'product_id_type' => 'EAN',
						'shop_sku' => $d['offer_sku'],
						'quantity' => $d['quantity'],
						'state_code' => 11, //11 = Nuevo, de momento no permitimos cambiar el estado
						'update_delete' => $d['update_delete'],
						'package-quantity' => 1
					);
				
				if($d['origin_price']>0)
				{
					//$offer_array['price'] = $d['origin_price'];
					//$offer_array['discount-price'] = $d['offer_price'];
					$offer_array['price'] = $d['offer_price'];
				}
				else
					$offer_array['price'] = $d['offer_price'];
				
				if ($d['offer_description'] != "")
				{
					$offer_array['description'] = $d['offer_description'];
					$offer_array['internal_description'] = $d['offer_description'];
				}
				array_push($offers, $offer_array);
			}
			
			$request->setOffers($offers);

			$result = $api->updateOffers($request);
			
			return array("status" => "OK","data" => $result);
		}
		catch (Exception $e) {
			return array("status" => "Error","message" => $e->getMessage());
		}
	}
	
	/**** PRODUCTOS *******/
	
	/**
     * Obtiene la información de un producto
     * @return  Array
     */
	public function get_product($ean)
	{
		
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey);
 			
			$productReferences = array(
      		'EAN' => array($ean),
      		'<type>' => array('EAN')
  			);
			$request = new GetProductsRequest($productReferences);
			$result = $api->getProducts($request);
	
			$producto = array();
			if(is_null($result) || empty($result) || count($result)==0)
			{
				return array("status" => "OK","data" => $producto);
			}
			$product = $result->current();
			
			$producto = array(
				"id" => $product->getId(),
				"id_type" => $product->getIdType(),
				"title" => $product->getTitle(),
				"category_code" => $product->getCategory()->getCode(),
				"category_name" => $product->getCategory()->getLabel(),
			);
			
			return array("status" => "OK","data" => $producto);
		}
		catch (Exception $e)
		{
			return array("status" => "Error","message" => "No es posible obtener el producto");
		}
	}
	
	/**
     * Crea un nuevo producto
     * @return  Array with import_id
     */
	public function import_product($data)
    {
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey, $this->shopID);
			
			// Add columns in top of file
			$cols = array_keys(reset($data));
			array_unshift($data, $cols);
			
			$file = \Mirakl\create_temp_csv_file($data);
			
			$request = new ProductImportRequest($file);
			$request->setFileName('EGO' . time() . '.csv');
			$result = $api->importProducts($request);
			
			return array("status" => "OK","data" => $result->getImportId());
    	}
		catch (Exception $e) {
			return array("status" => "Error","message" => "No es posible importar el producto. ".$e->getMessage());
		}
	}
	
	/**
     * Obtiene la información de una importación de un producto
     * @return  Array
     */
	public function get_import_product_result($import_id)
	{
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey);
			
			$request = new ProductImportStatusRequest($import_id);
 			$result = $api->getProductImportStatus($request);
					
			$importacion = array();
			if(is_null($result) || empty($result) || count($result)==0)
			{
				return array("status" => 200, "message" => "OK", "data" => $importacion);
			}
			
			$importacion = array(
				"status" => $result->getImportStatus(),
				"transformation_error" => $result->getTransformationErrorReport()
			);
			
			return array("status" => 200, "message" => "OK", "data" => $importacion);
		}
		catch (Exception $e)
		{
			return array("status" => 300, "message" => "Error", "data" => "No es posible obtener la información de la importación. ".$e->getMessage());
		}
	}
	
	
	/**** IMPORTACIONES *******/
	
	/**
     * Obtiene los errores de la transformación del fichero de importación de productos
     * @return  Array
     */
	public function get_import_transformation_result($import_id)
	{
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey);
			
			$request = new DownloadProductImportTransformationErrorReportRequest($import_id);
 			$result = $api->downloadProductImportTransformationErrorReport($request);
			
			$errors = array();
			if($result)
			{
				$file = $result->getFile();
				$primera = false;
				
				foreach($file as $row)
				{
					if(!$primera)
					{
						$primera = true;
						$sku_column = 1;
						$error_column = count($row)-1;
						$warning_column = count($row)-2;
						continue;
					}
					$errors[$row[$sku_column]] = array("warning"=>$row[$warning_column],"error"=>$row[$error_column]);
				}
			}
			return array("status" => 200, "message" => "OK", "data" => $errors);
		}
		catch (Exception $e)
		{
			return array("status" => "300","message" => "Error", "data" => "No es posible obtener la información de la importación. ".$e->getMessage());
		}
	}
	
	
	
	
	
	
	/**
     * Obtiene las características especiales que se pueden asignar a una categoría
     * @return  Array
     */
	public function get_category_attributes($category_code)
	{
		try{
			$api = new ShopApiClient($this->apiUrl, $this->apiKey, $this->shopID);
			
			$request = new GetAttributesRequest();
			$request->setHierarchyCode($category_code); // Opcional y no funciona
			//$request->setMaxLevel(3); // Optional (all children by default)
			$result = $api->getAttributes($request);
			$attributes = array();
			foreach($result as $res)
			{
				if($res->getHierarchyCode() != $category_code)
					continue;
		
				$attribute = array(
					"code" => $res->getCode(),
					"label" => $res->getLabel(),
					"example" => $res->getExample(),
					"type" => $res->getType(),
				);
				
				array_push($attributes,$attribute);
			}
			
			return array("status" => "OK","data" => $attributes);
		}
		catch(Exception $e)
		{
			return array("status" => "Error","message" => "No es posible obtener la información de atributos de categoria. ".$e->getMessage());
		}
	}
}
?>