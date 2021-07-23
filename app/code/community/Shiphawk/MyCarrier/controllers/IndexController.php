<?php
class Shiphawk_MyCarrier_IndexController extends Mage_Core_Controller_Front_Action
{
    /* suggest items type in product page */
    public function searchAction() {

        $search_tag = trim(strip_tags($this->getRequest()->getPost('search_tag')));

        $api_url = Mage::getStoreConfig('shiphawk/order/gateway_url');
        $api_key = Mage::getStoreConfig('shiphawk/order/api_key');

        $url_api = $api_url . 'items/search?q='.urlencode($search_tag).'&api_key='.$api_key;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url_api,
            CURLOPT_POST => false
        ));

        $resp = curl_exec($curl);
        $arr_res = json_decode($resp);

        $itemTypes = array();
        $responce = array();

        if(is_object($arr_res)) {
         if(($arr_res->error)) {
            $responce_html = '';
            $responce['shiphawk_error'] = $arr_res->error;
         }
        }else{
            foreach ((array) $arr_res as $el) {
                $itemTypes[$el->id] = $el->name;
            }

            $responce_html="<ul>";

            foreach($itemTypes as $id => $name) {
                $responce_html .='<li class="shiphawk_item_type_label" data-type-id='. $id .' onclick="setItemid(this)" >'.$name.'</li>';
            }

            $responce_html .="</ul>";
        }
        $responce['responce_html'] = $responce_html;
        curl_close($curl);

        $this->getResponse()->setBody( json_encode($responce) );
    }

    public function trackingAction() {
        $api_key_from_url = $this->getRequest()->getParam('api_key');
        $data_from_shiphawk = json_decode(file_get_contents('php://input'));

        Mage::log('DataFromShipHawk: ' . var_export($data_from_shiphawk, true), Zend_Log::INFO, 'shiphawk_tracking.log', true);

        $api_key = Mage::getStoreConfig('shiphawk/order/api_key');

        //curl -X POST -H Content-Type:application/json -d '{"event":"shipment.status_update","status":"in_transit","updated_at":"2017-11-22T10:23:16.702-08:00","shipment_id":1014270}' http://magento/index.php/shiphawk/index/tracking?api_key=secret

        if($api_key_from_url == $api_key) {
            try {
                $data_from_shiphawk = (array) $data_from_shiphawk;
                $shipment_increment_id = $data_from_shiphawk['source_system_id'];

                $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipment_increment_id);

                $email_shipment_status_updates = Mage::getStoreConfig('carriers/shiphawk_mycarrier/email_shipment_status_updates');
                $email_tracking_url_updates = Mage::getStoreConfig('carriers/shiphawk_mycarrier/email_tracking_url_updates');

                $comment = '';

                $event_created_at = $this->convertDateTime($data_from_shiphawk['updated_at']);

                if($data_from_shiphawk['event'] == 'shipment.status_update') {
                    switch ($data_from_shiphawk['status']) {
                        case 'in_transit':
                            $comment = "Shipment status changed to In Transit (" . $event_created_at['date'] . " at " . $event_created_at['time'] . "). Your shipment is with the carrier and is in transit.";
                            break;
                        case 'confirmed':
                            $comment = "Shipment status changed to Confirmed (" . $event_created_at['date'] . " at " . $event_created_at['time'] . "). Your shipment has been successfully confirmed.";
                            break;
                        case 'scheduled_for_pickup':
                            $comment = "Shipment status changed to Scheduled (" . $event_created_at['date'] . " at " . $event_created_at['time'] . "). Your shipment has been scheduled for pickup.";
                            break;
                        case 'agent_prep':
                            $comment = "Shipment status changed to Agent Prep (" . $event_created_at['date'] . " at " . $event_created_at['time'] . "). Your shipment is now being professionally prepared for carrier pickup.";
                            break;
                        case 'delivered':
                            $comment = "Shipment status changed to Delivered (" . $event_created_at['date'] . " at " . $event_created_at['time'] . "). Your shipment has been delivered!";
                            break;
                        case 'cancelled':
                            $comment = "Shipment status changed to Cancelled (" . $event_created_at['date'] . " at " . $event_created_at['time'] . "). Your shipment has been cancelled successfully.";
                            break;
                        case 'ready_for_carrier_pickup':
                            $comment = "Shipment status changed to Ready for Carrier Pickup (" . $event_created_at['date'] . " at " . $event_created_at['time'] . "). Your shipment has been successfully dispatched to the carrier.";
                            break;
                        default:
                            $comment = "Shipment status is ".$comment = "Shipment status is ";
                    }

                    $result = $shipment->addComment($comment);
                    if($email_shipment_status_updates) {
                        $shipment->sendUpdateEmail(true, $comment);
                    }
                }

                if($data_from_shiphawk['event'] == 'shipment.tracking_update') {
                    $comment = $data_from_shiphawk['updated_at'] . 'There is a tracking number available for your shipment - ' . $data_from_shiphawk['tracking_number'];
                    if ($data_from_shiphawk['tracking_url']) {
                        $comment .= ' <a href="' . $data_from_shiphawk['tracking_url'] . '" target="_blank">Click here to track.</a>';
                    }

                    $shipment->addComment($comment);
                    if($email_tracking_url_updates) {
                        $shipment->sendUpdateEmail(true, $comment);
                    }
                }

                $saveResult = $shipment->save();
            } catch (Mage_Core_Exception $e) {
                Mage::log('Add Shipment Tracking Comment Exception 1: ' . var_export($e, true), Zend_Log::INFO, 'shiphawk_tracking.log', true);
                Mage::logException($e);
            } catch (Exception $e) {
                Mage::log('Add Shipment Tracking Comment Exception 2: ' . var_export($e, true), Zend_Log::INFO, 'shiphawk_tracking.log', true);
                Mage::logException($e);
            }
        }
    }

    public function convertDateTime ($date_time) {
        ///2015-04-01T15:57:42Z
        $result = array();
        $t = explode('T', $date_time);
        $result['date'] = date("m/d/y", strtotime($t[0]));

        $result['time'] = date("g:i a", strtotime(substr($t[1], 0, -1)));

        return $result;
    }
}
