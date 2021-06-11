<?php

require_once(dirname(__DIR__, 1) . '/amazonBaseApi.php');

//http://docs.developer.amazonservices.com/en_US/fba_inbound/FBAInbound_PutTransportContent.html

class confirmOrder extends amazonBaseApi
{

    public function __construct($merchant_id, $mws_auth_token, $marketplaces_ids = array())
    {
        parent::__construct($merchant_id, $mws_auth_token, $marketplaces_ids);
    }

    /**
     *  Confirma un pedido
     *
     * @param string $fecha_desde
     * @return  Array { "itemId": string, "quantity": int }
     */

    public function confirmOrder($orderId, $carrieName, $shippingMethod = 'Economy', $trackingId, $amazonItemsIds)
    {

        self::add_class_amazon('MarketplaceWebService');

        $serviceUrl = "https://mws.amazonservices.es";

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
            APPLICATION_VERSION,
        );

        $marketplaceIdArray = array("Id" => $this->marketplaces_ids);

        $items = "";

        foreach ($amazonItemsIds as $item) {
            $items .= "<Item>
                <AmazonOrderItemCode>" . $item['itemId'] . "</AmazonOrderItemCode>
                <Quantity>" . intval($item['quantity']) . "</Quantity>
            </Item>";
        }

        $date = new DateTime();
        $date->setTimeZone(new DateTimeZone('UTC'));
        $date = $date->format('Y-m-d\TH:i:s');

        $feed = <<<EOD
                <?xml version="1.0" encoding="UTF-8"?>
                <AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <Header>
                        <DocumentVersion>1.01</DocumentVersion>
                        <MerchantIdentifier>$this->merchant_id</MerchantIdentifier>
                    </Header>
                    <MessageType>OrderFulfillment</MessageType>
                    <Message>
                        <MessageID>1</MessageID>
                        <OperationType>Update</OperationType> 
                        <OrderFulfillment>
                            <AmazonOrderID>$orderId</AmazonOrderID>
                            <FulfillmentDate>$date</FulfillmentDate>
                            <FulfillmentData>
                                <CarrierCode>$carrieName</CarrierCode>
                                <ShippingMethod>$shippingMethod</ShippingMethod> 
                                <ShipperTrackingNumber>$trackingId</ShipperTrackingNumber>
                            </FulfillmentData>
                            $items                
                        </OrderFulfillment>
                    </Message>
                </AmazonEnvelope> 
                EOD;

        
        $feedHandle = @fopen('php://memory', 'rw+');
        fwrite($feedHandle, $feed);
        rewind($feedHandle);

        //print_r($feedHandle);


        $request = new MarketplaceWebService_Model_SubmitFeedRequest();
        $request->setMerchant(MERCHANT_ID);
        //$request->setMarketplaceIdList($marketplaceIdArray);
        $request->setFeedType('_POST_ORDER_FULFILLMENT_DATA_');
        $request->setMarketplaceIdList($marketplaceIdArray);
        $request->setContentMd5(base64_encode(md5(stream_get_contents($feedHandle), true)));
        $request->setPurgeAndReplace(false);
        $request->setFeedContent($feedHandle);
        $request->setMWSAuthToken($this->mws_auth_token); // Optional
        
        $response = $service->submitFeed($request);

        $submitFeedResult = $response->getSubmitFeedResult();
        $feedSubmissionInfo = $submitFeedResult->getFeedSubmissionInfo();

        return array("status" => "200", "data" => $feedSubmissionInfo->getFeedSubmissionId());


    }


}