<?php

namespace DpdConnect\classes\Handlers;

use DpdConnect\classes\Connect\Product;
use DpdConnect\classes\shippingmethods\DPDShippingMethod;

class OrderListActions
{
    public static function handle()
    {
        // For old WooCommerce versions
        add_filter('bulk_actions-edit-shop_order', [self::class, 'handleAddBulkActions']);
        // For new WooCommerce versions
        add_filter('bulk_actions-woocommerce_page_wc-orders', [self::class, 'handleAddBulkActions'], 10, 3);

        add_filter('woocommerce_package_rates', function ($shippingRates) {
            /** @var \WC_Shipping_Rate $shippingRate */
            foreach ($shippingRates as $shippingRate) {
                if ($shippingRate->get_method_id() === 'dpd_shipping_method') {
                    $shippingMethod = new DPDShippingMethod($shippingRate->get_instance_id());

                    if (true === $shippingMethod->is_dpd_saturday) {
                        $shippingRates = $shippingMethod->hide($shippingRates);
                    }
                }
            }

            return $shippingRates;
        }, 10, 1);
    }

    public static function handleAddBulkActions(array $bulk_actions)
    {
        $bulk_actions['dpdconnect_create_labels_bulk_action'] = __('Create DPD Labels');

        $product = new Product();
        foreach ($product->getAllowedProducts() as $dpdProduct) {
            $label = $dpdProduct['name'];
            // Check if label already contains 'DPD' to prevent multiple 'DPD' in label
            if (strpos(strtolower($dpdProduct['name']), 'dpd') === false) {
                $label = 'DPD ' . $label;
            }

            $bulk_actions['dpdconnect_create_' . $dpdProduct['code'] . '_labels_bulk_action'] = __('Create ' . $label . ' Labels');
        }

        return $bulk_actions;
    }
}
