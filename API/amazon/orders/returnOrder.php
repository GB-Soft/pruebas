<?php

require_once(dirname(__DIR__, 1) . '/amazonBaseApi.php');


class returnOrder extends amazonBaseApi
{
    public function __construct($merchant_id, $mws_auth_token, $marketplaces_ids = array())
    {
        parent::__construct($merchant_id, $mws_auth_token, $marketplaces_ids);
    }

    /**
     *  Confirma un pedido
     *
     * @param string $fecha_desde
     * @return  array --> Refund / Cancel
     */

    /*
    ['CustomerReturn','GeneralAdjustment','CouldNotShip','DifferentItem','Abandoned','CustomerCancel','PriceError'
    'ProductOutofStock','CustomerAddressIncorrect','CustomerAddressIncorrect','Exchange','Other','CarrierCreditDecision'
    'RiskAssessmentInformationNotValid','CarrierCoverageFailure','TransactionRecord','Undeliverable','RefusedDelivery']
   */

    public function returnOrder($orderId, $actionType,$amazonOrderItems){

        self::add_class_amazon('MarketplaceWebService');

        $serviceUrl = "https://mws-eu.amazonservices.com";

        $items = "";

        foreach ($amazonOrderItems as $item) {
            $items .= "<AdjustedItem>
                <AmazonOrderItemCode>" . $item['AmazonOrderItemCode'] . "</AmazonOrderItemCode>
                <AdjustmentReason>" .  intval($item['quantity'])  . "</AdjustmentReason>
            </AdjustedItem>";
        }

        $feed = <<<EOD
                <?xml version="1.0" encoding="UTF-8"?>
                <AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <Header>
                        <DocumentVersion>1.01</DocumentVersion>
                        <MerchantIdentifier>$this->merchant_id</MerchantIdentifier>
                    </Header>
                    <MessageType>OrderAdjustment</MessageType>
                    <Message>
                        <MessageID>1</MessageID>
                        <OperationType>Update</OperationType>
                        <OrderAdjustment>
                            <AmazonOrderID>$orderId</AmazonOrderID>
                            <ActionType>$actionType</ActionType>
                            $items
                        </OrderAdjustment>
                    </Message>
                </AmazonEnvelope> 
                EOD;


        $feedHandle = @fopen('php://temp', 'rw+');
        fwrite($feedHandle, $feed);
        rewind($feedHandle);

        $parameters = array(
            'Merchant' => $this->merchant_id,
            'MarketplaceIdList' => $this->marketplaces_ids,
            'FeedType' => '_POST_PAYMENT_ADJUSTMENT_DATA_',
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
            APPLICATION_VERSION,
            );


        //$response = invokeSubmitFeed($service, $request);
        $response = $service->submitFeed($request);

        $responseDef = $response->getSubmitFeedResult();
        //return $responseDef->FeedSubmissionInfo->FeedSubmissionId;

        return array("status" => "200", "data" => $responseDef->FeedSubmissionInfo->FeedSubmissionId);

    }


}