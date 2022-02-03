<?php

class Shiphawk_Order_Model_StatusMapper
{
    public function map($status)
    {
        switch ($status) {
            case 'canceled':
                return 'cancelled';
            case 'complete':
                return 'shipped';
            case 'processing':
                return 'partially_shipped';
            case 'holded':
                return 'on_hold';
            case 'pending':
                return 'new';
            default:
                return $status;
        }
    }
}
