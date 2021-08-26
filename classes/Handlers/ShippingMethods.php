<?php

namespace DpdConnect\classes\Handlers;

use DpdConnect\classes\Connect\Product;
use DpdConnect\classes\Option;
use DpdConnect\classes\producttypes\B2B;
use DpdConnect\classes\producttypes\Fresh;
use DpdConnect\classes\producttypes\Parcelshop;
use DpdConnect\classes\producttypes\Predict;
use DpdConnect\classes\shippingmethods\DPDShippingMethod;

class ShippingMethods
{
    public static function handle()
    {
        add_filter('woocommerce_shipping_methods', [self::class, 'add']);
        add_action('woocommerce_init', [self::class, 'addSettingFilters']);
    }

    public static function add($methods)
    {
        $methods['dpd_shipping_method'] = new DPDShippingMethod();

        return $methods;
    }

    public static function addSettingFilters()
    {
        $shipping_methods = WC()->shipping->get_shipping_methods();

        foreach($shipping_methods as $shipping_method) {
//            if($shipping_method->id == 'dpd_pickup') {
            if ($shipping_method->id === 'dpd_shipping_method' && $shipping_method->is_dpd_pickup) {
                continue;
            }

            add_filter('woocommerce_shipping_instance_form_fields_' . $shipping_method->id, [self::class, 'addCustomSettings']);
        }
    }

    public static function addCustomSettings($settings)
    {
        $accountType = Option::accountType();
        $products = new Product();

        $availableOptions = [];

        foreach ($products->getAllowedProducts() as $product) {
            $availableOptions[$product['code']] = $product['name'];
        }

        $settings['dpd_method_type'] = [
            'title' => 'DPD Label Type',
            'type' => 'select',
            'class'   => 'wc-enhanced-select',
            'default' => '',
            'options' => $availableOptions,
        ];

        return $settings;
    }
}
