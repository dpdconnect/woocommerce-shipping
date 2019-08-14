<?php

namespace DpdConnect\classes\Handlers;

use DpdConnect\classes\Option;
use DpdConnect\classes\shippingmethods\DPD_E10;
use DpdConnect\classes\shippingmethods\DPD_E12;
use DpdConnect\classes\shippingmethods\DPD_E18;
use DpdConnect\classes\shippingmethods\DPD_Pickup;
use DpdConnect\classes\shippingmethods\DPD_Classic;
use DpdConnect\classes\shippingmethods\DPD_Predict;
use DpdConnect\classes\shippingmethods\DPD_Saturday;

class ShippingMethods
{
    public static function handle()
    {
        add_filter('woocommerce_shipping_methods', [self::class, 'add']);
    }

    public static function add($methods)
    {
        $accountType = Option::accountType();

        // Activate B2B Shipping methods if B2B is selected under settings or admin is logged in
        if ($accountType == 'b2b') {
            // Only B2B shipping methods
            $methods['dpd_classic'] = new DPD_Classic();
            $methods['dpd_e10'] = new DPD_E10();
            $methods['dpd_e12'] = new DPD_E12();
            $methods['dpd_e18'] = new DPD_E18();
        }

        // Activate B2C Shipping methods if B2C is selected under settings or admin is logged in
        if ($accountType == 'b2c') {
            // Only B2C shipping methods
            $methods['dpd_predict'] = new DPD_Predict();
            $methods['dpd_pickup'] = new DPD_Pickup();
        }

        // Saterday shipping method for B2C and B2B
        $methods['dpd_saturday'] = new DPD_Saturday();

        return $methods;
    }
}
