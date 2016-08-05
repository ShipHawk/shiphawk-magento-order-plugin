<?php

class Shiphawk_Order_Model_Source_Ordersync
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'1', 'label' => 'Send orders to ShipHawk'),
            array('value'=>'0', 'label' => 'Do not send orders to ShipHawk'),
        );
    }
}
