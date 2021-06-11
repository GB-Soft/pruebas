<?php

require_once(dirname(__DIR__, 1) . '/amazonBaseApi.php');

class Feed extends amazonBaseApi
{

    public function __construct($merchant_id, $mws_auth_token, $marketplaces_ids = array())
    {
        parent::__construct($merchant_id, $mws_auth_token, $marketplaces_ids);
    }

    public function submitFeed($feed,$feedType){

        self::add_class_amazon('MarketplaceWebService');

        $serviceUrl = "https://mws-eu.amazonservices.com";

        $items = "";

        $feedHandle = @fopen('php://temp', 'rw+');
        fwrite($feedHandle, $feed);
        rewind($feedHandle);
		
        $parameters = array(
            'Merchant' => $this->merchant_id,
            'MarketplaceIdList' => $this->marketplaces_ids,
            'FeedType' => $feedType,
            'FeedContent' => $feedHandle,
            'PurgeAndReplace' => false,
            'ContentMd5' => base64_encode(md5(stream_get_contents($feedHandle), true)),
            'MWSAuthToken' => $this->mws_auth_token, // Optional
        );

        $request = new MarketplaceWebService_Model_SubmitFeedRequest($parameters);

        $config = array(
            'ServiceURL' => $serviceUrl,
            'ProxyHost' => null,
            'ProxyPort' => -1,
            'MaxErrorRetry' => 3,
        );

        $service = new MarketplaceWebService_Client(
            AWS_ACCESS_KEY_ID,
            AWS_SECRET_ACCESS_KEY,
            $config,
            APPLICATION_NAME,
            APPLICATION_VERSION
        );


        //$response = invokeSubmitFeed($service, $request);
        try{
            $response = $service->submitFeed($request);
            //print_r($lol->getSubmitFeedResult());
            $responseDef = $response->getSubmitFeedResult();

            return array("status" => "200", "data" => $responseDef->getFeedSubmissionInfo()->FeedSubmissionId); //->FeedSubmissionInfo()->FeedSubmissionId
        }
        catch (Exception $e){
            return array("status" => "300","message" => "Error", "data" => "No es posible subir el feed. ".$e->getMessage());
        }

    }

    public function getFeedSubmisionList($submissionId, $marketplacesIds)
	{
        $serviceUrl = "https://mws-eu.amazonservices.com";

        $config = array (
            'ServiceURL' => $serviceUrl,
            'ProxyHost' => null,
            'ProxyPort' => -1,
            'MaxErrorRetry' => 3,
        );

        $marketplaceIdArray = array("Id" => $marketplacesIds);

        self::add_class_amazon('MarketplaceWebService');

        $service = new MarketplaceWebService_Client(
            AWS_ACCESS_KEY_ID,
            AWS_SECRET_ACCESS_KEY,
            $config,
            APPLICATION_NAME,
            APPLICATION_VERSION);


        $request = new MarketplaceWebService_Model_GetFeedSubmissionListRequest();

        $request->setMerchant($this->merchant_id);
        $request->setMWSAuthToken($this->mws_auth_token); // Optional

        $request->setMarketplace($marketplaceIdArray);
        $statusList = new MarketplaceWebService_Model_IdList();
        $statusList->setId($submissionId);
		
		
        try{
            $request->setFeedSubmissionIdList($statusList);

            $response = $service->getFeedSubmissionList($request);
			
            $getFeedSubmissionListResult = $response->getGetFeedSubmissionListResult();
            $feedSubmissionInfoList = $getFeedSubmissionListResult->getFeedSubmissionInfoList();

            foreach ($feedSubmissionInfoList as $feedSubmissionInfo) 
			{
                $status = $feedSubmissionInfo->getFeedProcessingStatus();
			
                if($status === '_DONE_')
                   return array("status" => "200", "data" => true);
                else
                    return array("status" => "200", "data" => false);
            }
        }
        catch (Exception $e){
            return array("status" => "300","message" => "Error", "data" => "No es posible ver el estado de la petición. ".$e->getMessage());

        }


    }

    public function getFeedSubmisionResult($submissionId)
	{

        $serviceUrl = "https://mws-eu.amazonservices.com";

        $config = array (
            'ServiceURL' => $serviceUrl,
            'ProxyHost' => null,
            'ProxyPort' => -1,
            'MaxErrorRetry' => 3,
        );

        self::add_class_amazon('MarketplaceWebService');

        $service = new MarketplaceWebService_Client(
            AWS_ACCESS_KEY_ID,
            AWS_SECRET_ACCESS_KEY,
            $config,
            APPLICATION_NAME,
            APPLICATION_VERSION
        );

        $request = new MarketplaceWebService_Model_GetFeedSubmissionResultRequest();
        $request->setMerchant($this->merchant_id);
        $request->setFeedSubmissionId(trim($submissionId));
        $request->setFeedSubmissionResult(@fopen('php://memory', 'rw+'));
        $request->setMWSAuthToken($this->mws_auth_token); // Optional
		
        try
		{
            $response = $service->getFeedSubmissionResult($request);
			
            if ($response->isSetGetFeedSubmissionResultResult()) 
			{
                $getFeedSubmissionResultResult = $response->getGetFeedSubmissionResultResult();

                //$xml = simplexml_load_string(stream_get_contents($request->getFeedSubmissionResult()), "SimpleXMLElement", LIBXML_NOCDATA);
				
				$content = stream_get_contents($request->getFeedSubmissionResult());
				$xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);
				//No siempre el resultado viene en XML, en ocasiones viene en txt separado por tabulaciones
				if($xml)
				{
					$objJsonDocument = json_encode($xml);
                	$arrOutput = json_decode($objJsonDocument, TRUE);
					
					if($arrOutput['Message']['ProcessingReport']['MessagesSuccessful'] === 1 )
					{
						return array(
							"status" => "200",
							"message" => "Success",
							"data" => "Pedido confirmado con éxito" );
					}
					else if($arrOutput['Message']['ProcessingReport']['MessagesWithError'] === 1)
					{
						return array(
							"status" => "200",
							"message" => "Error",
							"data" => $arrOutput['Message']['ProcessingReport']['Result']['ResultDescription']
						);
					}
					
				}
				else
				{
					$csv = str_getcsv($content, "\n");
					$errores = array();
					foreach($csv as $line)
					{
						$arr_linea = str_getcsv(trim($line), "\t");
						if($arr_linea[3] == 'Error' && $arr_linea[0]=="1") //si el primer campo es 0 es un resumen global del fichero, nos interesa que sea "1" que es cada uno de los productos
							$errores[$arr_linea[1]][] = $arr_linea[4]; //$arr_linea[1] contiene el SKU y $arr_linea[4] el error
					}
					if(empty($errores))
						return array("status" => "200",	"message" => "Success",	"data" => "Pedido confirmado con éxito" );
					else
						return array("status" => "201",	"message" => "Success",	"data" => $errores );
				}	
            }

        } catch (Exception $e) {
            return array("status" => "300","message" => "Error", "data" => "Error ".$e->getMessage());
        }
    }




}