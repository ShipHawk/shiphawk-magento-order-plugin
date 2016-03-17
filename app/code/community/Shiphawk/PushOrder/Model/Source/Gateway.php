<?php

class Shiphawk_PushOrder_Model_Source_Gateway
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'https://shiphawk.com/api/v4/', 'label' => Mage::helper('adminhtml')->__('Live')),
            array('value'=>'https://sandbox.shiphawk.com/api/v4/', 'label' => Mage::helper('adminhtml')->__('Sandbox')),
        );
    }
}
