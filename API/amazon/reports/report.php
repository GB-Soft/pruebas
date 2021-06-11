<?php

require_once(dirname(__DIR__, 1) . '/amazonBaseApi.php');

class Report extends amazonBaseApi
{

    public function __construct($merchant_id, $mws_auth_token, $marketplaces_ids = array())
    {
        parent::__construct($merchant_id, $mws_auth_token, $marketplaces_ids);
    }

    public function requestReport($reportType,$reportOptions)
	{

        self::add_class_amazon('MarketplaceWebService');

        $serviceUrl = "https://mws-eu.amazonservices.com";

        $config = array (
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
            APPLICATION_VERSION);

        $parameters = array (
            'Merchant' => MERCHANT_ID,
            'MarketplaceIdList' => $this->marketplaces_ids,
            'ReportType' => $reportType,
            'ReportOptions' => $reportOptions,
            'MWSAuthToken' => $this->mws_auth_token, // Optional
        );
		
        $request = new MarketplaceWebService_Model_RequestReportRequest($parameters);
		
        try{
            $response = $service->requestReport($request);
            $requestResult = $response->getRequestReportResult();

            $reportRequestInfo = $requestResult->getReportRequestInfo();

            if ($reportRequestInfo->isSetReportRequestId())
            {
                $requestId =  $reportRequestInfo->getReportRequestId();
                return array(
                    "status" => "200",
                    "message" => "Success",
                    "data" => $requestId );
            }
            else{
                return array(
                    "status" => "300",
                    "message" => "Error no se ha generado el requestId, intentalo en un rato.",
                    "data" => false );
            }
        }
        catch (Exception $e){
            return array("status" => "300",
                        "message" => "Error",
                        "data" => "Error, tienes que volver a subir el reporte. ".$e->getMessage());
        }

    }

    public function requestListReport($reportId)
	{

        self::add_class_amazon('MarketplaceWebService');

        $serviceUrl = "https://mws-eu.amazonservices.com";

        $config = array (
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
            APPLICATION_VERSION);

        // $service = new MarketplaceWebService_Mock();

        $parameters = array (
            'Merchant' => MERCHANT_ID,
            'ReportRequestIdList'=> array('Id'=>$reportId),
            'MWSAuthToken' => $this->mws_auth_token, // Optional
        );
		
        $request = new MarketplaceWebService_Model_GetReportRequestListRequest($parameters);

        try{
            $response = $service->getReportRequestList($request);
			
            $listResults = $response->getGetReportRequestListResult()->getReportRequestInfoList();

            foreach ($listResults as $reportRequestInfo) {

                if ($reportRequestInfo->isSetReportRequestId()){

                    $status = $reportRequestInfo->getReportProcessingStatus();

                    $generatedReportId = false;
                    if ($reportRequestInfo->isSetGeneratedReportId())
                    {
                        $generatedReportId = $reportRequestInfo->getGeneratedReportId();
                    }

                    if($status == '_DONE_'){
                        return array(
                            "status" => "200",
                            "message" => "Reporte procesado. ",
                            "data" => array('generatedReportId'=> $generatedReportId));
                    }
                    else{ // aqui habria que volver a pedir otra vez esto en por ejemplo media hora
                        return array(
                            "status" => "300",
                            "message" => "Reporte todavía no procesado, vuelva a comprobarlo en un rato. ",
                            "data" => false);
                    }
                }
            }
        }
        catch (Exception $e){
            return array(
                "status" => "300",
                "message" => "Error al comprobar el estado del reporte. ",
                "data" => $e->getMessage());
        }

    }

    public function getReportId($reportId)
	{

        self::add_class_amazon('MarketplaceWebService');

        $serviceUrl = "https://mws-eu.amazonservices.com";

        $config = array (
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
            APPLICATION_VERSION);

        // $service = new MarketplaceWebService_Mock();

        $parameters = array (
            'Merchant' => MERCHANT_ID,
            'ReportRequestIdList'=> array('ReportRequestId'=>$reportId),
            'MWSAuthToken' => $this->mws_auth_token, // Optional
        );

        $request = new MarketplaceWebService_Model_GetReportRequestListRequest($parameters);

        try {
            $response = $service->getReportList($request);
            if ($response->isSetGetReportListResult()) {
                $getReportListResult = $response->getGetReportListResult();
                $reportInfoList = $getReportListResult->getReportInfoList();
                foreach ($reportInfoList as $reportInfo) {
                    if ($reportInfo->isSetReportId()) {
                        $reportId = $reportInfo->getReportId();
                        return array(
                            "status" => "200",
                            "message" => "Reporte id generado correctamente ",
                            "data" => $reportId
                        );
                    } else {
                        return array(
                            "status" => "300",
                            "message" => "Reporte id no generado, intentelo en un rato ",
                            "data" => false
                        );
                    }

                }
            }
            return array(
                "status" => "300",
                "message" => "Error. No éxiste ninguna petición asociada al request id facilitado. ",
                "data" => false
            );
        }
        catch (Exception $e){
            return array(
                "status" => "300",
                "message" => "Error. ",
                "data" => $e->getMessage()
            );
        }
    }

    public function getReport($generatedReportId)
	{

        self::add_class_amazon('MarketplaceWebService');

        $serviceUrl = "https://mws-eu.amazonservices.com";

        $config = array (
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

        $parameters = array (
            'Merchant' => MERCHANT_ID,
            'Report' => @fopen('php://memory', 'rw+'),
            'ReportId' => $generatedReportId,
            'MWSAuthToken' =>$this->mws_auth_token, // Optional
        );

        $request = new MarketplaceWebService_Model_GetReportRequest($parameters);

        try{
            $response = $service->getReport($request);

            //$folder = __DIR__ . '/'.'_GET_XML_BROWSE_TREE_DATA_'.'_'.'29592904810018723'.'.xml';
            //$dest = fopen($folder, 'wr+');

            if($response->isSetGetReportResult()){
                $getReportResult = $response->getGetReportResult();

                //$content = stream_get_contents($request->getReport();
                //echo $content;
                //fputs($dest, $content);

                //$dom = new DOMDocument;
                //$dom->preserveWhiteSpace = FALSE;
                //$dom->loadXML($content);

                //$dom->formatOutput = TRUE;

                $xml = simplexml_load_string(stream_get_contents($request->getReport()), "SimpleXMLElement", LIBXML_NOCDATA);
                $objJsonDocument = json_encode($xml);
                $arrOutput = json_decode($objJsonDocument, TRUE);

                return array(
                    "status" => "200",
                    "message" => "Report generated",
                    "data" => $arrOutput
                );

            }
            else{
                return array(
                    "status" => "300",
                    "message" => "Report not generated",
                    "data" => false
                );
            }
        }
        catch (Exception $e){
            return array(
                "status" => "300",
                "message" => "Error generating the final report",
                "data" => $e->getMessage()
            );
        }
    }

}
