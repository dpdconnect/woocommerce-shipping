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
        add_action('woocommerce_init', [self::class, 'addSettingFilters']);
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

    public static function addSettingFilters()
    {
        $shipping_methods = WC()->shipping->get_shipping_methods();
        foreach($shipping_methods as $shipping_method) {
            if($shipping_method->id == 'dpd_pickup') {
               continue;
            }
            add_filter('woocommerce_shipping_instance_form_fields_' . $shipping_method->id, [self::class, 'addCustomSettings']);
        }
    }

    public static function addCustomSettings($settings)
    {

        $settings['dpd_method_type'] = [
            'title' => 'DPD Label Type',
            'type' => 'select',
            'class'   => 'wc-enhanced-select',
            'default' => 'predict',
            'options' => [
                'predict' => 'DPD Predict(Home)',
                'classic'    => 'DPD Classic',
                'saturday'    => 'DPD Saturday',
                'return'    => 'DPD Return',
                'express_10'    => 'DPD Express 10',
                'express_12'    => 'DPD Express 12',
                'express_18'    => 'DPD Express 18',
            ],
        ];

        return $settings;
    }
}
