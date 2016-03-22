<?php

class Shiphawk_Order_Model_Observer
{
    public function pushOrder($observer)
    {
        if (Mage::getStoreConfig('shiphawk/order/active') != 1) {
            return ;
        }

        $url = Mage::getStoreConfig('shiphawk/order/gateway_url');
        $key = Mage::getStoreConfig('shiphawk/order/api_key');
        $client = new Zend_Http_Client($url . 'orders?api_key=' . $key);

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();

        $itemsRequest = [];
        foreach ($order->getAllItems() as $item) {
            /** @var Mage_Sales_Model_Order_Item $item */
            $itemsRequest[] = array(
                'source_system_id' => $item->getProductId(),
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'quantity' => $item->getQtyOrdered(),
                'price' => $item->getPrice(),
                'length' => $item->getLength(),
                'width' => $item->getWidth(),
                'height' => $item->getHeight(),
                'weight' => $item->getWeight(),
                'item_type' => $item->getProductType(),
                'unpacked_item_type_id' => 0,
                'handling_unit_type' => '',
                'hs_code' => '',
            );
        }

        $orderRequest = json_encode(
            array(
                'order_number' => $order->getId(),
                'source_system' => 'magento',
                'source_system_id' => $order->getStoreId(),
                'source_system_processed_at' => '',
                'origin_address' => $this->prepareAddress($order->getBillingAddress()),
                'destination_address' => $this->prepareAddress($order->getShippingAddress()),
                'order_line_items' => $itemsRequest,
                'total_price' => $order->getGrandTotal(),
                'shipping_price' => $order->getShippingAmount(),
                'tax_price' => $order->getTaxAmount(),
                'items_price' => $order->getSubtotal(),
                'status' => $this->statusMap($order->getStatus()),
                'fulfillment_status' => $this->fulfillmentStatusMap($order->getStatus()),
            )
        );

        Mage::log('ShipHawk Request: ' . $orderRequest, Zend_Log::INFO, 'shiphawk_order.log');
        $client->setRawData($orderRequest, 'application/json');
        try {
            $response = $client->request(Zend_Http_Client::POST);
        } catch (Exception $e) {
            Mage::logException($e);
        }
        Mage::log('ShipHawk Response: ' . var_export($response, true), Zend_Log::INFO, 'shiphawk_order.log');
    }

    protected function statusMap($status)
    {
        switch ($status) {
            case 'Canceled':
                return 'Canceled';
            case 'Complete':
                return 'Closed';
            case 'Pending':
                return 'Open';
            default:
                return 'N/A';
        }
    }

    protected function fulfillmentStatusMap($status)
    {
        switch ($status) {
            case 'Complete':
                return 'Fulfilled';
            case 'Processing':
                return 'Partially Fufilled';
            default:
                return 'N/A';
        }
    }

    protected function prepareAddress(Mage_Sales_Model_Order_Address $address)
    {
        return array(
            'name' => $address->getFirstname() . ' '
                . $address->getMiddlename() . ' '
                . $address->getLastname(),
            'company' => $address->getCompany(),
            'street1' => $address->getStreet1(),
            'street2' => $address->getStreet2(),
            'phone_number' => $address->getTelephone(),
            'city' => $address->getCity(),
            'state' => $address->getRegionCode(),
            'country' => $address->getCountryId(),
            'zip'  => $address->getPostcode(),
            'email' => $address->getEmail(),
            'code'  => $address->getAddressType(),
        );
    }
}
