<?php
	include ("inc/config.php");
	include("functions.php");
	
	$accion = !isset($_POST['accion']) || $_POST['accion']=="" ? $_GET['accion'] : $_POST['accion'];
	
	/*
		DEVUELVE EL SHOP ID
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY (api_key)
	*/
	if(strtoupper($accion)=='GET_SHOP_ID')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->get_shop_id();
		}
		deliver_response(200, "OK", $result);
	}
	
	/*
		DEVUELVE LISTADOS DE "RAZONES" para los Reembolsos DE LAS PLATAFORMAS
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY  (api_key)
	
	*/
	elseif(strtoupper($accion)=='GET_REASONS_LIST_REFUND')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->get_reasons_refund();
			deliver_response(200, "OK", $result);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	}
	
	/*
	******************* CATEGORIAS **************************
	*/
	
	
	/*
		DEVUELVE LISTADOS DE CATEGORIAS
		PARÁMETROS:
		- Plataforma (plataforma)
		* CARREFOUR:
			- @string API KEY (api_key)
	
	*/
	elseif(strtoupper($accion)=='GET_CATEGORIES')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->get_categories();
			deliver_response(200, "OK", $result['data']);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	}
	
	/*
	******************* PEDIDOS **************************
	*/
	
	/*
		DEVUELVE LOS PEDIDOS DE UNA PLATAFORMA DESDE UNA FECHA HASTA LA FECHA ACTUAL
		PARÁMETROS:
		- @string Plataforma (plataforma)
		- @string (date) Fecha Desde (fecha_desde)
		* CARREFOUR:
			- @string API KEY (api_key)
		* AMAZON:
			- @string MERCHANT ID = SELLER ID (merchant_id)
			- @string MWS AUTH TOKEN (mws_auth_token)
			- @array(string) MARKETPLACES IDS (marketplaces_ids) 
		
	*/
	elseif(strtoupper($accion)=='GET_ORDERS')
	{
		if (!isset($_POST['plataforma']))
		{
			deliver_response(300, "ERROR", "Debe indicar la plataforma");
			exit;
		}
			
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			if (!isset($_POST['api_key']) || !isset($_POST['fecha_desde']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			
			
			$fecha_desde = date("d-m-Y",strtotime($_POST['fecha_desde']));
			//$fecha_hasta = date("d-m-Y",strtotime($_GET['fecha_hasta']));
			$result = $api->get_orders($fecha_desde);
			deliver_response(200, "OK", $result);
			exit;
		}
		elseif(strtoupper($_POST['plataforma'])=='AMAZON')
		{
			if (!isset($_POST['merchant_id']) || !isset($_POST['mws_auth_token']) || !isset($_POST['marketplaces_ids']) || !isset($_POST['fecha_desde']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
			
			include ('API/amazon/orders/order.php');
			$api = new Order($_POST['merchant_id'] ,$_POST['mws_auth_token'], $_POST['marketplaces_ids']);
			
			
			$result = $api->get_orders($_POST['fecha_desde'], isset($_POST['fecha_update_desde']) && $_POST['fecha_update_desde']!="" ? $_POST['fecha_update_desde'] : null);
			
			deliver_response($result['status'], $result['message'], $result['data']);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	}
	/*
		DEVUELVE LOS PEDIDOS DE UNA PLATAFORMA DESDE CON EL NÚMERO DE PEDIDO
		PARÁMETROS:
		- @string Plataforma (plataforma)
		- @string Número Pedido (order_number)
		* CARREFOUR:
			- @string API KEY (api_key)
		* AMAZON:
			- @string MERCHANT ID = SELLER ID (merchant_id)
			- @string MWS AUTH TOKEN (mws_auth_token)
	*/
	elseif(strtoupper($accion)=='GET_ORDER')
	{
		if (!isset($_POST['plataforma']))
		{
			deliver_response(300, "ERROR", "Debe indicar la plataforma");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			if (!isset($_POST['api_key']) || !isset($_POST['order_number']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
			if(strtoupper($_POST['plataforma'])=='CARREFOUR')
			{	
				include ('API/carrefour/carrefour.php');
				$api = new Carrefour_API($_POST['api_key']);
				$result = $api->get_order_by_number($_POST['order_number']);
				
				if ($result['status'] == "OK") 
					deliver_response(200, "OK", $result['data']);
				else
					deliver_response(300, "Error", $result['message']);
				exit;
			}
		}
		elseif(strtoupper($_POST['plataforma'])=='AMAZON')
		{
			if (!isset($_POST['merchant_id']) || !isset($_POST['mws_auth_token']) || !isset($_POST['order_number']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
			
			include ('API/amazon/orders/order.php');
			$api = new Order($_POST['merchant_id'] ,$_POST['mws_auth_token'], $_POST['marketplaces_ids']);
			
			$result = $api->get_order($_POST['order_number']);
			deliver_response($result['status'], $result['message'], $result['data']);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	}
	/*
		ACEPTA UN PEDIDO (PASA DE EN ESPERA A PENDIENTE DE ENVÍO)
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY (api_key)
		- Número de pedido (numero_pedido)
		- Líneas (Array) (lineas)
	*/
	elseif(strtoupper($accion)=='ACCEPT_ORDER')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']) || !isset($_POST['order_number']) || !isset($_POST['lines']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->accept_order($_POST['order_number'],$_POST['lines']);
			if ($result['status'] == "OK") 
				deliver_response(200, "OK");
			else
				deliver_response(300, "Error", $result['message']);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	}
	/*
		OBTIENE LOS MENSAJES DE UN PEDIDO
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY (api_key)
		- Número de pedido (numero_pedido)
	*/
	elseif(strtoupper($accion)=='GET_ORDER_MESSAGES')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']) || !isset($_POST['order_number']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->get_order_messages($_POST['order_number']);
			deliver_response(200, "OK", $result['data']);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	}
	/*
		MANDA UN MENSAJE A UN PEDIDO
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY (api_key)
		- Número de pedido (numero_pedido)
		- Asunto
		- Mensaje
	*/
	elseif(strtoupper($accion)=='SEND_ORDER_MESSAGE')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']) || !isset($_POST['order_number']) || !isset($_POST['subject']) || !isset($_POST['body']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->send_order_message($_POST['order_number'],$_POST['subject'],$_POST['body']);
			deliver_response(200, "OK", $result['data']);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	}
	/*
		OBTIENE LOS TRANSPORTISTAS DE LA PLATAFORMA
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY (api_key)
	*/
	elseif(strtoupper($accion)=='GET_CARRIERS')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->get_carriers();
			deliver_response(200, "OK", $result['data']);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	}
	/*
		CONFIRMA EL ENVÍO DE UN PEDIDO
		PARÁMETROS:
		- Plataforma (plataforma)
		- Número de pedido (numero_pedido)
		* CARREFOUR:
			- @string API KEY (api_key)
		* AMAZON:
			- @string MERCHANT ID = SELLER ID (merchant_id)
			- @string MWS AUTH TOKEN (mws_auth_token)
			- @array(string) MARKETPLACES IDS (marketplaces_ids) 
			- @string Nombre del Transportista (carrieName)
			- @string método Envío (shippingMethod)
			- @string Nº Tracking (trackingId)
			- @array Elementos [Formato: itemId y quantity ] (amazonItemsIds)
	*/
	elseif(strtoupper($accion)=='SHIP_ORDER')
	{
		if (!isset($_POST['plataforma']))
		{
			deliver_response(300, "ERROR", "Debe indicar la plataforma");
			exit;
		}
		
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			if (!isset($_POST['api_key']) || !isset($_POST['order_number']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
		
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->ship_order($_POST['order_number']);
			deliver_response(200, "OK");
			exit;
		}
		elseif(strtoupper($_POST['plataforma'])=='AMAZON')
		{
			if (!isset($_POST['merchant_id']) || !isset($_POST['mws_auth_token']) || !isset($_POST['marketplaces_ids']) || !isset($_POST['numero_pedido']) || !isset($_POST['carrieName']) || !isset($_POST['shippingMethod']) || !isset($_POST['trackingId']) || !isset($_POST['amazonItemsIds']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
			
			include ('API/amazon/orders/confirmOrder.php');
			
			$api = new confirmOrder($_POST['merchant_id'] ,$_POST['mws_auth_token'], $_POST['marketplaces_ids']);
			$result = $api->confirmOrder($_POST['numero_pedido'], $_POST['carrieName'], $_POST['shippingMethod'], $_POST['trackingId'], $_POST['amazonItemsIds']);
			
			deliver_response(200, "OK", $result);
			exit;
		}
			
		deliver_response(300, "Error", "No data recived");
	}
	/*
		ACTUALIZA INFORMACIÓN DE TRACKING DE UN PEDIDO
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY (api_key)
		- Número de pedido (numero_pedido)
		- Código del Transportista
		- Nombre del Transportista
		- URL de Seguimeinto
		- Número de Seguimiento (OPCIONAL)
	*/
	elseif(strtoupper($accion)=='UPDATE_TRACKING_ORDER')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']) || !isset($_POST['order_number']) || !isset($_POST['carrier_code']) 
			|| !isset($_POST['carrier_name']) || !isset($_POST['carrier_url']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->update_tracking_order($_POST['order_number'], $_POST['carrier_code'], $_POST['carrier_name'], $_POST['carrier_url'], $_POST['tracking_number']);
			deliver_response(200, "OK");
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	}
	/*
		REEMBOLSA UN PEDIDO
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY (api_key)
		- Número de pedido (order_number)
		- Lineas (lines) -> associative array with keys (amount, reason_code, order_line_id, quantity, shipping_amount) per line;
	*/
	elseif(strtoupper($accion)=='REFUND_ORDER')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']) || !isset($_POST['order_number']) || !isset($_POST['lines']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->refund_order($_POST['order_number'], $_POST['lines']);
			deliver_response(200, "OK", $$result);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	}
	/*
		OBTIENE LOS DOCUMENTOS DE UN PEDIDO
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY (api_key)
		- Número de pedido (order_number)
	*/
	elseif(strtoupper($accion)=='GET_ORDER_DOCUMENTS')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']) || !isset($_POST['order_number']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->get_order_documents($_POST['order_number']);
			deliver_response(200, "OK", $result['data']);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	} 
	/*
		SUBE UN DOCUMENTO A UN PEDIDO
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY (api_key)
		- Número de pedido (order_number)
		- Tipo de fichero (file_type)
		- Nombre del fichero (file_name)
		- URL del documento (file_url)
	*/
	elseif(strtoupper($accion)=='SEND_DOCUMENT_ORDER')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']) || !isset($_POST['order_number']) || !isset($_POST['file_type']) || !isset($_POST['file_name']) || !isset($_POST['file_url']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			if(!is_dir('tmp'))
				mkdir('tmp');
			if(!is_dir('tmp/'.$_POST['plataforma']))
				mkdir('tmp/'.$_POST['plataforma']);
			$newfile = $_SERVER['DOCUMENT_ROOT'].'/tmp/'.$_POST['plataforma'].'/'.$_POST['file_name'];
			if (copy($_POST['file_url'], $newfile))
			{	
				$file = new \SplFileObject($newfile);
				include ('API/carrefour/carrefour.php');
				$api = new Carrefour_API($_POST['api_key']);
				$result = $api->send_order_document($_POST['order_number'], $_POST['file_type'], $_POST['file_name'], $file);
				if($result['status'] == "OK")
					deliver_response(200, "OK", $result['data']);
				else
					deliver_response(300, "Error", $result['message']);
				unlink($newfile);
				exit;
			}
			else
			{
				deliver_response(300, "Error", "No se puede subir el fichero");
				exit;
			}
			
			
		}
		deliver_response(300, "Error", "No data recived");
	} 
	/*
		ELIMINA UN DOCUMENTO DE UN PEDIDO
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY (api_key)
		- Número de pedido (order_number)
		- Número de Documento (document_number)
	*/
	elseif(strtoupper($accion)=='DELETE_DOCUMENT_ORDER')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']) || !isset($_POST['order_number']) || !isset($_POST['document_number']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->delete_document_order($_POST['document_number']);
			deliver_response(200, "OK", $result['data']);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	} 
	
	/*
	******************* OFERTAS **************************
	*/
	
	/*
		OBTIENE UNA OFERTA
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY (api_key)
		- SKU de la Oferta (offer_sku)
	*/
	elseif(strtoupper($accion)=='GET_OFFER')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']) || !isset($_POST['offer_sku']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->get_offer($_POST['offer_sku']);
			deliver_response(200, "OK", $result['data']);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	} 
	
	/*
		ACTUALIZA UNA OFERTA
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY (api_key)
		- EAN del producto (ean)
		- SKU de la Oferta (offer_sku)
		- Precio base (origin_price)
		- Precio oferta (offer_price)
		- Cantidad (quantity)
		- Descripción (offer_description)
	*/
	elseif(strtoupper($accion)=='UPDATE_OFFER')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']) || !isset($_POST['ean']) || !isset($_POST['offer_sku']) || !isset($_POST['offer_price']) || !isset($_POST['quantity']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->update_offer($_POST['ean'], $_POST['offer_sku'], $_POST['origin_price'], $_POST['offer_price'], $_POST['quantity'], $_POST['offer_description']);
			deliver_response(200, "OK", $result['data']);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	} 
	
	/*
		ACTUALIZA LISTADO DE OFERTAS
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY (api_key)
		- Array to generate CSV (data)
	*/
	elseif(strtoupper($accion)=='UPDATE_OFFERS')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']) || !isset($_POST['data']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->update_offers($_POST['data']);
			deliver_response(200, "OK", $result['data']);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	} 
	
	
	/*
	******************* PRODUCTOS **************************
	*/
	
	/*
		OBTIENE LA INFORMACIÓN DE UN PRODUCTO
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY (api_key)
		- EAN del producto (ean)
	*/
	elseif(strtoupper($accion)=='GET_PRODUCT')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']) || !isset($_POST['ean']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->get_product($_POST['ean']);
			deliver_response(200, "OK", $result['data']);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	} 
	
	/*
		IMPORTA UN PRODUCTO
		PARÁMETROS:
		- Plataforma (plataforma)
		* CARREFOUR
			- API KEY (api_key)
			- Array to generate CSV (data)
		* AMAZON:
			- @string MERCHANT ID = SELLER ID (merchant_id)
			- @string MWS AUTH TOKEN (mws_auth_token)
			- @array(string) MARKETPLACES IDS (marketplaces_ids) 
			- @string CONTENIDO CSV (content_csv)
		
	*/
	elseif(strtoupper($accion)=='IMPORT_PRODUCT')
	{
		if (!isset($_POST['plataforma']))
		{
			deliver_response(300, "ERROR", "Debe indicar la plataforma");
			exit;
		}
		
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			if(!isset($_POST['api_key']) || !isset($_POST['data']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->import_product($_POST['data']);
			deliver_response(200, "OK", $result['data']);
			exit;
		}
		
		if(strtoupper($_POST['plataforma'])=='AMAZON')
		{
			if (!isset($_POST['merchant_id']) || !isset($_POST['mws_auth_token']) || !isset($_POST['marketplaces_ids']) || !isset($_POST['content_csv']))
			{
				deliver_response(300, "ERROR", $_POST);
				exit;
			}
			
			include_once('API/amazon/feed/feed.php');
			$feed = new Feed($_POST['merchant_id'],$_POST['mws_auth_token'],$_POST['marketplaces_ids']);
			$result = $feed->submitFeed($_POST['content_csv'],'_POST_FLAT_FILE_LISTINGS_DATA_');
			
			deliver_response(200, "OK", $result);
			exit;
		}
		
		deliver_response(300, "Error", "No data recived");
	}
	/*
		OBTIENE LA INFORMACIÓN DE UNA IMPORTACIÓN DE PRODUCTO
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY (api_key)
		- ID de la Importación (import_id)
	*/
	elseif(strtoupper($accion)=='GET_IMPORT_PRODUCT_RESULT')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']) || !isset($_POST['import_id']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->get_import_product_result($_POST['import_id']);
			deliver_response($result['status'], $result['message'], $result['data']);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	} 
	/*
		OBTIENE LA INFORMACIÓN DE ERRORES DE TRANSFORMACIÓN DE UN FICHERO DE IMPORTACIÓN
		PARÁMETROS:
		- Plataforma (plataforma)
		- API KEY (api_key)
		- ID de la Importación (import_id)
	*/
	elseif(strtoupper($accion)=='GET_IMPORT_TRANSFORMATION_RESULT')
	{
		if (!isset($_POST['plataforma']) || !isset($_POST['api_key']) || !isset($_POST['import_id']))
		{
			deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->get_import_transformation_result($_POST['import_id']);
			deliver_response($result['status'], $result['message'], $result['data']);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	}  
	
	/*
		OBTIENE LOS ATRIBUTOS DE UNA CATEGORÍA PARA EL FICHERO DE IMPORTACIÓN
		PARÁMETROS:
		- Plataforma (plataforma)
		* CARREFOUR
			- API KEY (api_key)
			- Código de la Categoría (category_code)
		* AMAZON:
			- @string MERCHANT ID = SELLER ID (merchant_id)
			- @string MWS AUTH TOKEN (mws_auth_token)
			- @string ID IDIOMA (id_lang)
			- @string Nombre Fichero (file_name)
	*/
	elseif(strtoupper($accion)=='GET_CATEGORY_ATTRIBUTES')
	{
		if (!isset($_POST['plataforma']))
		{
			deliver_response(300, "ERROR", "Debe indicar la plataforma");
			exit;
		}
		
		if(strtoupper($_POST['plataforma'])=='CARREFOUR')
		{
			if (!isset($_POST['api_key']) || !isset($_POST['category_code']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
		
			include ('API/carrefour/carrefour.php');
			$api = new Carrefour_API($_POST['api_key']);
			$result = $api->get_category_attributes($_POST['category_code']);
			deliver_response(200, "OK", $result['data']);
			exit;
		}
		
		if(strtoupper($_POST['plataforma'])=='AMAZON')
		{
			if (!isset($_POST['merchant_id']) || !isset($_POST['mws_auth_token']) || !isset($_POST['id_lang']) || !isset($_POST['file_name']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
			
			$path = "API/amazon/products/";
			$result = shell_exec("python3.7 ".$path."main.py ".$path."files/".$_POST['id_lang']."/".$_POST['file_name']." 2>&1");
			
			deliver_response(200, "OK", json_decode($result));
			exit;
		}
		
		deliver_response(300, "Error", "No data recived");
	} 
	
	
	
	
	/******************** AMAZON FEEDS ************************************/
	
	
	/*
		OBTIENE LA LISTA DE LOS FEED ENVIADOS
		PARÁMETROS:
		- Plataforma (plataforma)
		- Número de feed (submission_id)
		
		* AMAZON:
			- @string MERCHANT ID = SELLER ID (merchant_id)
			- @string MWS AUTH TOKEN (mws_auth_token)
	*/
	elseif(strtoupper($accion)=='GET_FEED_SUBMISION_LIST')
	{
		if (!isset($_POST['plataforma']))
		{
			deliver_response(300, "ERROR", "Debe indicar la plataforma");
			exit;
		}
		
		if(strtoupper($_POST['plataforma'])=='AMAZON')
		{
			if (!isset($_POST['merchant_id']) || !isset($_POST['mws_auth_token']) || !isset($_POST['submission_id']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
			include_once('API/amazon/feed/feed.php');
			$feed = new Feed($_POST['merchant_id'],$_POST['mws_auth_token']);
			$result = $feed->getFeedSubmisionList($_POST['submission_id']);
			
			deliver_response(200, "OK", $result);
			exit;
		}
	}
	/*
		OBTIENE EL ESTADO DE UN FEED ENVIADO
		PARÁMETROS:
		- Plataforma (plataforma)
		- Número de feed (submission_id)
		
		* AMAZON:
			- @string MERCHANT ID = SELLER ID (merchant_id)
			- @string MWS AUTH TOKEN (mws_auth_token)
	*/
	elseif(strtoupper($accion)=='GET_FEED_SUBMISION_RESULT')
	{
		if (!isset($_POST['plataforma']))
		{
			deliver_response(300, "ERROR", "Debe indicar la plataforma");
			exit;
		}
		
		if(strtoupper($_POST['plataforma'])=='AMAZON')
		{
			if (!isset($_POST['merchant_id']) || !isset($_POST['mws_auth_token']) || !isset($_POST['submission_id']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
			include_once('API/amazon/feed/feed.php');
			$feed = new Feed($_POST['merchant_id'],$_POST['mws_auth_token']);
			$result = $feed->getFeedSubmisionResult($_POST['submission_id']);
			
			deliver_response(200, "OK", $result);
			exit;
		}
	}
	
	/******************** AMAZON REPORTS ************************************/
	
	
	/*
		PIDE UN REPORTE
		PARÁMETROS:
		- Plataforma (plataforma)
		* AMAZON:
			- @string MERCHANT ID = SELLER ID (merchant_id)
			- @string MWS AUTH TOKEN (mws_auth_token)
			- @string TIPO DE REPORTE (report_type)
			- @array OPCIONES (report_options) - Optional. Format example = MarketplaceId=ATVPDKIKX0DER;BrowseNodeId=15706661   //array('RootNodesOnly'=>true, 'BrowseNodeId'=>'XXXX')
	*/
	elseif(strtoupper($accion)=='REQUEST_REPORT')
	{	
		if (!isset($_POST['plataforma']))
		{
			deliver_response(300, "ERROR", "Debe indicar la plataforma");
			exit;
		}
		
		if(strtoupper($_POST['plataforma'])=='AMAZON')
		{
			if (!isset($_POST['merchant_id']) || !isset($_POST['mws_auth_token']) || !isset($_POST['report_type']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
			include_once('API/amazon/reports/report.php');
			$report = new Report($_POST['merchant_id'],$_POST['mws_auth_token']);
			$result = $report->requestReport($_POST['report_type'], $_POST['report_options']);
			
			if($result['status'] == "200")
				deliver_response(200, "OK", $result['data']);
			else
				deliver_response(300, "Error", $result['data']);
			exit;
		}
	}
	
	/*
		OBTIENE EL ID REPORT Y EL ESTADO DE UN REPORTE SOLICITADO EN LA FUNCIÓN REQUEST_REPORT
		PARÁMETROS:
		- Plataforma (plataforma)
		* AMAZON:
			- @string MERCHANT ID = SELLER ID (merchant_id)
			- @string MWS AUTH TOKEN (mws_auth_token)
			- @string ID DE PETICIÓN (REQUEST) (id_request)
	*/
	elseif(strtoupper($accion)=='LIST_REPORT')
	{	
		if (!isset($_POST['plataforma']))
		{
			deliver_response(300, "ERROR", "Debe indicar la plataforma");
			exit;
		}
		
		if(strtoupper($_POST['plataforma'])=='AMAZON')
		{
			if (!isset($_POST['merchant_id']) || !isset($_POST['mws_auth_token']) || !isset($_POST['id_request']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
			include_once('API/amazon/reports/report.php');
			$report = new Report($_POST['merchant_id'],$_POST['mws_auth_token']);
			$result = $report->requestListReport($_POST['id_request']);
			
			if($result['status'] == "200")
				deliver_response(200, "OK", $result['data']);
			else
				deliver_response(300, "Error", $result['message']);
			exit;
		}
	}
	
	/*
		OBTIENE EL RESULTADO DE UN REPORTE
		PARÁMETROS:
		- Plataforma (plataforma)
		* AMAZON:
			- @string MERCHANT ID = SELLER ID (merchant_id)
			- @string MWS AUTH TOKEN (mws_auth_token)
			- @string ID DE REPORTE (id_report)
	*/
	elseif(strtoupper($accion)=='GET_REPORT')
	{	
		if (!isset($_POST['plataforma']))
		{
			deliver_response(300, "ERROR", "Debe indicar la plataforma");
			exit;
		}
		
		if(strtoupper($_POST['plataforma'])=='AMAZON')
		{
			if (!isset($_POST['merchant_id']) || !isset($_POST['mws_auth_token']) || !isset($_POST['id_report']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
			include_once('API/amazon/reports/report.php');
			$report = new Report($_POST['merchant_id'],$_POST['mws_auth_token']);
			$result = $report->getReport($_POST['id_report']);
			
			if($result['status'] == "200")
				deliver_response(200, "OK", $result['data']);
			else
				deliver_response(300, "Error", $result['data']);
			exit;
		}
	}
	
	/******************** AMAZON FEEDS ************************************/
	/*
		OBTIENE EL EL ESTADO DE UN FEED
		PARÁMETROS:
		- Plataforma (plataforma)
		* AMAZON:
			- @string MERCHANT ID = SELLER ID (merchant_id)
			- @string MWS AUTH TOKEN (mws_auth_token)
			- @array Marketplaces IDS (marketplaces_ids)
			- @string ID FEED (id_feed)
	*/
	elseif(strtoupper($accion)=='GET_FEED_LIST')
	{	
		if (!isset($_POST['plataforma']))
		{
			deliver_response(300, "ERROR", "Debe indicar la plataforma");
			exit;
		}
		
		if(strtoupper($_POST['plataforma'])=='AMAZON')
		{
			if (!isset($_POST['merchant_id']) || !isset($_POST['mws_auth_token']) || !isset($_POST['marketplaces_ids']) || !isset($_POST['id_feed']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
			include_once('API/amazon/feed/feed.php');
			$feed = new Feed($_POST['merchant_id'],$_POST['mws_auth_token']);
			$result = $feed->getFeedSubmisionList($_POST['id_feed'],$_POST['marketplaces_ids']);
			
			if($result['status'] == "200")
				deliver_response(200, "OK", $result['data']);
			else
				deliver_response(300, "Error", $result['data']);
			exit;
		}
	}
	
	/*
		OBTIENE EL RESULTADO DE UN FEED
		PARÁMETROS:
		- Plataforma (plataforma)
		* AMAZON:
			- @string MERCHANT ID = SELLER ID (merchant_id)
			- @string MWS AUTH TOKEN (mws_auth_token)
			- @string ID FEED (id_feed)
	*/
	elseif(strtoupper($accion)=='GET_FEED_RESULT')
	{	
		if (!isset($_POST['plataforma']))
		{
			deliver_response(300, "ERROR", "Debe indicar la plataforma");
			exit;
		}
		
		if(strtoupper($_POST['plataforma'])=='AMAZON')
		{
			if (!isset($_POST['merchant_id']) || !isset($_POST['mws_auth_token']) || !isset($_POST['id_feed']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
			include_once('API/amazon/feed/feed.php');
			$feed = new Feed($_POST['merchant_id'],$_POST['mws_auth_token']);
			$result = $feed->getFeedSubmisionResult($_POST['id_feed']);
			
			if($result['status'] == "200" || $result['status'] == "201") //200 todo Ok, 201 Ok pero productos con errores
				deliver_response($result['status'], "OK", $result['data']);
			else
				deliver_response(300, "Error", $result['data']);
			exit;
		}
	}
	
	/************* OTRAS FUNCIONES DE AMAZON ****************/
	/*
		OBTIENE EL ASIN DE UN SKU EN UN MARKETPLACE
		PARÁMETROS:
		- Plataforma (plataforma)
		* AMAZON:
			- @string MERCHANT ID = SELLER ID (merchant_id)
			- @string MWS AUTH TOKEN (mws_auth_token)
			- @array Marketplace ID (marketplace_id)
			- @string SKU (sku)
	*/
	elseif(strtoupper($accion)=='GET_ASIN_SKU')
	{
		if (!isset($_POST['plataforma']))
		{
			deliver_response(300, "ERROR", "Debe indicar la plataforma");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='AMAZON')
		{
			if (!isset($_POST['merchant_id']) || !isset($_POST['mws_auth_token']) || !isset($_POST['marketplace_id']) || !isset($_POST['sku']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
			include_once('API/amazon/products/product.php');
			$product = new Product($_POST['merchant_id'],$_POST['mws_auth_token'],$_POST['marketplace_id']);
			$result = $product->get_product_ASIN_by_SKU($_POST['sku']);

			deliver_response($result['status'], $result['message'], $result['data']);
			exit;
		}
		deliver_response(300, "Error", "No data recived");
	} 
	
	/*
		SUBE UNA PLANTILLA CUSTOM Y DEVUELVE EL CONTENIDO
		PARÁMETROS:
		- Plataforma (plataforma)
		- Nombre del fichero (file_name)
		- URL del documento (file_url)
	*/
	elseif(strtoupper($accion)=='UPLOAD_AND_GET_TEMPLATE')
	{
		if (!isset($_POST['plataforma']))
		{
			deliver_response(300, "ERROR", "Debe indicar la plataforma");
			exit;
		}
		if(strtoupper($_POST['plataforma'])=='AMAZON')
		{
			if (!isset($_POST['file_name']) || !isset($_POST['file_url']))
			{
				deliver_response(300, "ERROR", "Debe indicar todos los parámetros");
				exit;
			}
			
			//El fichero se tiene que quedar almacenado
			$path = $_SERVER['DOCUMENT_ROOT'].'/API/amazon/products/files/';
			if(!is_dir($path))
				mkdir($path);
			$path .= "custom/";
			if(!is_dir($path))
				mkdir($path);
			
			$newfile = $path.$_POST['file_name'];
			if (copy($_POST['file_url'], $newfile))
			{	
				$path = "API/amazon/products/";
				$result = shell_exec("python3.7 ".$path."main.py ".$newfile." 2>&1");
				//unlink($newfile);
				deliver_response(200, "OK", $result);
				exit;
			}
			else
			{
				deliver_response(300, "Error", "No se puede subir el fichero");
				exit;
			}
			
			
		}
		deliver_response(300, "Error", "No data recived");
	} 
	
?>