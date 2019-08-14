<?php

namespace DpdConnect\classes\Handlers;

use DpdConnect\classes\shippingmethods\DPD_Saturday;

class OrderListActions
{
    public static function handle()
    {
        add_filter('bulk_actions-edit-shop_order', function ($bulk_actions) {
            $bulk_actions['dpdconnect_create_labels_bulk_action'] = __('Create DPD Labels');
            $bulk_actions['dpdconnect_create_return_labels_bulk_action'] = __('Create DPD Return Labels');
            return $bulk_actions;
        });

        add_filter('woocommerce_package_rates', function ($shippingMethods) {
            $saturday = new DPD_saturday();
            return $saturday->hide($shippingMethods);
        }, 10, 1);
    }
}
