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
                return 'new';
            default:
                return 'new';
        }
    }
}
