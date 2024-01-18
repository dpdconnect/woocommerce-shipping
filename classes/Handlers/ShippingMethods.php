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

        // Only show custom fields for dpd_shipping_method (excluding pick-up)
        foreach($shipping_methods as $shipping_method) {
            if ($shipping_method->id === 'dpd_shipping_method' && $shipping_method->is_dpd_pickup) {
//            if ($shipping_method->id !== 'dpd_shipping_method' || ($shipping_method->id === 'dpd_shipping_method' && $shipping_method->is_dpd_pickup)) {
                continue;
            }

            add_filter('woocommerce_shipping_instance_form_fields_' . $shipping_method->id, [self::class, 'addCustomSettings']);
            add_action( 'woocommerce_after_shipping_rate', [ self::class, 'action_after_shipping_rate' ], 20, 2 );
        }
    }

    public static function addCustomSettings($settings)
    {
        $accountType = Option::accountType();
        $products = new Product();

        $availableOptions = [];

        foreach ($products->getAllowedProducts() as $product) {
            $availableOptions[$product['code']] = sprintf('%s (%s)', $product['name'], $product['description']);
        }

        $settings['dpd_method_type'] = [
            'title' => 'DPD Label Type',
            'type' => 'select',
            'class'   => 'wc-enhanced-select',
            'default' => '',
            'options' => $availableOptions,
        ];

        $settings['dpd_checkout_description'] = [
            'title' => 'DPD Checkout description',
            'type' => 'text',
            'default' => '',
            'description' => 'Please leave this field empty and submit the form to fill it with the default checkout description supplied by DPD',
            'sanitize_callback' => [ self::class, 'sanitize_dpd_checkout_description' ]
        ];

        return $settings;
    }

    public static function sanitize_dpd_checkout_description($value)
    {
        // Return the default checkout description if the description is not set yet
        if (true === empty($value)) {
            $selectedProduct = $_POST['data']['woocommerce_dpd_shipping_method_dpd_method_type'];

            if (null === $selectedProduct) {
                return $value;
            }

            $product = new Product();

            return $product->getProductByCode($selectedProduct)['descriptionCheckout'];
        }

        return $value;
    }

    // Function that gets called to display the description on checkout and cart page
    public static function action_after_shipping_rate ( $method, $index ) {
        if ($method->method_id !== 'dpd_shipping_method') {
            return;
        }

        $shippingMethodSettings = get_option('woocommerce_' . $method->method_id . '_' . $method->instance_id . '_settings');
        $checkoutDescription = $shippingMethodSettings['dpd_checkout_description'] ?? null;

        if (null === $checkoutDescription) {
            return;
        }

        echo __(sprintf('<p>%s</p>', $checkoutDescription));
    }
}
