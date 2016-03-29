<?php

class Shiphawk_Order_Model_Api2_Order_Comment extends Mage_Api2_Model_Resource
{
    const STATE_NEW             = 'new';
    const STATE_PENDING_PAYMENT = 'pending_payment';
    const STATE_PROCESSING      = 'processing';
    const STATE_COMPLETE        = 'complete';
    const STATE_CLOSED          = 'closed';
    const STATE_CANCELED        = 'canceled';
    const STATE_HOLDED          = 'holded';
    const STATE_PAYMENT_REVIEW  = 'payment_review';

    protected $stateMap = [
        'canceled' => Mage_Sales_Model_Order::STATE_CANCELED,
        'closed' => Mage_Sales_Model_Order::STATE_CLOSED,
        'complete' => Mage_Sales_Model_Order::STATE_COMPLETE,
        'fraud' => Mage_Sales_Model_Order::STATE_HOLDED,
        'holded' => Mage_Sales_Model_Order::STATE_HOLDED,
        'payment_review' => Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
        'paypal_canceled_reversal' => Mage_Sales_Model_Order::STATE_CANCELED,
        'paypal_reversed' => Mage_Sales_Model_Order::STATE_CANCELED,
        'pending' => Mage_Sales_Model_Order::STATE_NEW,
        'pending_payment' => Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
        'pending_paypal' => Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
        'processing' => Mage_Sales_Model_Order::STATE_PROCESSING,
    ];

    public function _update($data)
    {
        $orderId = $this->getRequest()->getParam('id');
        $order = Mage::getModel('sales/order')->load($orderId);
        /** @var Mage_Sales_Model_Order $order */
        $status = isset($data['status']) ? $data['status'] : false;
        $comment = isset($data['comment']) ? $data['comment'] : '';
        $notified = isset($data['notified']) ? $data['notified'] : false;
        $order->setState($this->stateMap[$status], $status, $comment, $notified);
        $order->save();
    }
}
