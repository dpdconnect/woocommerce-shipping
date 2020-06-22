<?php

namespace DpdConnect\classes\Handlers;

use DpdConnect\classes\shippingmethods\DPD_Saturday;

class OrderListActions
{
    public static function handle()
    {
        add_filter('bulk_actions-edit-shop_order', function ($bulk_actions) {
            $bulk_actions['dpdconnect_create_labels_bulk_action'] = __('Create DPD Labels');
            $bulk_actions['dpdconnect_create_predict_labels_bulk_action'] = __('Create DPD Predict(Home) Labels');
            $bulk_actions['dpdconnect_create_classic_labels_bulk_action'] = __('Create DPD Classic Labels');
            $bulk_actions['dpdconnect_create_parcelshop_labels_bulk_action'] = __('Create DPD ParcelShop Labels');
            $bulk_actions['dpdconnect_create_saturday_labels_bulk_action'] = __('Create DPD Saturday Labels');
            $bulk_actions['dpdconnect_create_express_10_labels_bulk_action'] = __('Create DPD Express 10 Labels');
            $bulk_actions['dpdconnect_create_express_12_labels_bulk_action'] = __('Create DPD Express 12 Labels');
            $bulk_actions['dpdconnect_create_express_18_labels_bulk_action'] = __('Create DPD Express 18 Labels');
            $bulk_actions['dpdconnect_create_return_labels_bulk_action'] = __('Create DPD Return Labels');
            return $bulk_actions;
        });

        add_filter('woocommerce_package_rates', function ($shippingMethods) {
            $saturday = new DPD_saturday();
            return $saturday->hide($shippingMethods);
        }, 10, 1);
    }
}
