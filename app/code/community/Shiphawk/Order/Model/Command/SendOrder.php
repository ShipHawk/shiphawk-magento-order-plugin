<?php

class Shiphawk_Order_Model_Command_SendOrder
{
    public function execute(Mage_Sales_Model_Order $order)
    {
        Mage::log('building order object for Shiphawk');
        $url = Mage::getStoreConfig('shiphawk/order/gateway_url');
        $key = Mage::getStoreConfig('shiphawk/order/api_key');
        $client = new Zend_Http_Client($url . 'orders');
        $client->setHeaders('X-Api-Key', $key);

        $send_items_as_unpacked = Mage::getStoreConfig('carriers/shiphawk_mycarrier/send_items_as_unpacked');

        $itemsRequest = [];
        $shippingRateId = '';

        Mage::log('rates array:...');
        $SHRates = Mage::getSingleton('core/session')->getSHRateAarray();
        Mage::log($SHRates);

        foreach($SHRates as $rateRow){
            $sh_service_title = $rateRow->service_name;
            if (substr($rateRow->service_name, 0, strlen($rateRow->carrier)) !== $rateRow->carrier){
                $sh_service_title = $rateRow->carrier . ' - ' . $rateRow->service_name;
            }

            if(($rateRow->carrier . ' - ' . $sh_service_title)  == $order->getShippingDescription()){
                $shippingRateId = $rateRow->id;
            }
        }

        $skuColumn = Mage::getStoreConfig('shiphawk/datamapping/sku_column');
        $SimpleItems = array();
        foreach($order->getAllVisibleItems() as $item){

            if($item->getHasChildren()) {
                foreach($item->getChildrenItems() as $child) {
                    $SimpleItems[] = $child;
                }
            }
            else if(!$item->getHasParent())  {
                $SimpleItems[] = $item;
            }
        }

        foreach ($SimpleItems as $item) {
            $product_id = $item->getProductId();
            $product = Mage::getModel('catalog/product')->load($product_id);
            $item_weight = $item->getWeight();

            if ($send_items_as_unpacked) {
                $item_type = 'unpacked';
            } else {
                $item_type = $item_weight <= 70 ? 'parcel' : 'handling_unit';
            }

            $weight = $item_weight;
            if ($item_type == 'parcel') {
                $weight *= 16;
            }

            $handling_unit_type = '';
            if ($item_type == 'handling_unit'){
                $handling_unit_type = 'box';
            }

            if ($item->getParentItemId()) {
              $source_system_id = $item->getParentItemId();
            } else {
              $source_system_id = $item->getItemId();
            }

            $value = $product->getData('shiphawk_item_value') ? $product->getData('shiphawk_item_value') : $product->getFinalPrice();

            $itemsRequest[] = array(
                'name' => $item->getName(),
                'sku' => $product->getData($skuColumn),
                'quantity' => $item->getQtyOrdered(),
                'value' => $value,
                'length' => $item->getLength(),
                'width' => $item->getWidth(),
                'height' => $item->getHeight(),
                'weight' => $weight,
                'can_ship_parcel' => true,
                'item_type' => $item_type,
                'handling_unit_type' => $handling_unit_type,
                'source_system_id' => $source_system_id,
            );
        }

        $orderRequest = json_encode(
            array(
                'order_number' => $order->getIncrementId(),
                'source' => 'magento',
                'source_system' => 'magento',
                'source_system_domain' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
                'source_system_id' => $order->getEntityId(),
                'source_system_processed_at' => $order->getCreatedAt(),
                'requested_rate_id' => $shippingRateId,
                'requested_shipping_details'=> $order->getShippingDescription(),
                'origin_address' => $this->getOriginAddress(),
                'destination_address' => $this->prepareAddress($order->getShippingAddress()),
                'order_line_items' => $itemsRequest,
                'total_price' => $order->getGrandTotal(),
                'shipping_price' => $order->getShippingAmount(),
                'tax_price' => $order->getTaxAmount(),
                'items_price' => $order->getSubtotal(),
                'status' => 'new',
            )
        );

        Mage::log('ShipHawk Request: ' . $orderRequest, Zend_Log::INFO, 'shiphawk_order.log', true);
        $client->setRawData($orderRequest, 'application/json');
        try {
            $response = $client->request(Zend_Http_Client::POST);
            $responseArray = json_decode($response->getBody());
            $shiphawkOrderId = $responseArray->id;

            $order->setShiphawkOrderId($shiphawkOrderId);
        } catch (Exception $e) {
            Mage::log('Error when setting attribute: ' . var_export($e, true), Zend_Log::INFO, 'shiphawk_order.log', true);

            Mage::logException($e);
        }
    }

    protected function prepareAddress(Mage_Sales_Model_Order_Address $address)
    {
        return array(
            'name'              => $address->getFirstname() . ' '
                . $address->getMiddlename() . ' '
                . $address->getLastname(),
            'company'           => $address->getCompany(),
            'street1'           => $address->getStreet1(),
            'street2'           => $address->getStreet2(),
            'phone_number'      => $address->getTelephone(),
            'city'              => $address->getCity(),
            'state'             => $address->getRegionCode(),
            'country'           => $address->getCountryId(),
            'zip'               => $address->getPostcode(),
            'email'             => $address->getEmail(),
            'is_residential'    =>  'true'
        );
    }

    protected function getOriginAddress()
    {
        return array(
            'name' => Mage::getStoreConfig('general/store_information/name'),
            'phone_number' => Mage::getStoreConfig('general/store_information/phone'),
            'street1' => Mage::getStoreConfig('shipping/origin/street_line1'),
            'street2' => Mage::getStoreConfig('shipping/origin/street_line2'),
            'city' => Mage::getStoreConfig('shipping/origin/city'),
            'state' => Mage::getModel('directory/region')
                ->load(Mage::getStoreConfig('shipping/origin/region_id'))
                ->getCode(),
            'country' => Mage::getStoreConfig('shipping/origin/country_id'),
            'zip' => Mage::getStoreConfig('shipping/origin/postcode'),
        );
    }
}
