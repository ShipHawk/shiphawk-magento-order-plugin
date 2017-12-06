<?php

class Shiphawk_Order_Model_Source_Gateway
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'https://shiphawk.com/api/v4/', 'label' => Mage::helper('adminhtml')->__('Live')),
            array('value'=>'https://sandbox.shiphawk.com/api/v4/', 'label' => Mage::helper('adminhtml')->__('Sandbox')),
            array('value'=>'https://stage.shiphawk.com/api/v4/', 'label' => Mage::helper('adminhtml')->__('STAGE')),
            array('value'=>'https://qa.shiphawk.com/api/v4/', 'label' => Mage::helper('adminhtml')->__('QA')),
            array('value'=>'http://127.0.0.1:3000/api/v4/', 'label' => Mage::helper('adminhtml')->__('LOCAL'))
        );
    }
}
