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

        $responce_array = array();
        $responce = array();

        // $helper = Mage::helper('shiphawk_shipping');

        if(is_object($arr_res)) {
         if(($arr_res->error)) {
            // $helper->shlog($arr_res->error);
            $responce_html = '';
            $responce['shiphawk_error'] = $arr_res->error;
         }
        }else{
            foreach ((array) $arr_res as $el) {
                $responce_array[$el->id] = $el->name.' ('.$el->category. ' - ' . $el->subcategory_name . ')';
            }

            $responce_html="<ul>";

            foreach($responce_array as $key=>$value) {
                $responce_html .='<li class="type_link" id='.$key.' onclick="setItemid(this)" >'.$value.'</li>';
            }

            $responce_html .="</ul>";
        }
        $responce['responce_html'] = $responce_html;
        curl_close($curl);

        $this->getResponse()->setBody( json_encode($responce) );
    }
}
