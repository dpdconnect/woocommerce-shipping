<?php

namespace DpdConnect\classes\Handlers;

use DpdConnect\classes\enums\NoticeType;
use DpdConnect\classes\TypeHelper;
use DpdConnect\classes\Handlers\Notice;

class ShippingAttributes
{
    public static function handle()
    {
        add_action('woocommerce_product_options_shipping', [self::class, 'add']);
        add_action('woocommerce_process_product_meta_simple', [self::class, 'save']);
    }

    public static function add()
    {
        /** @var \WC_Product $product_object */
        global $product_object;

        $fields[] = [
            'id'            => 'dpd_hs_code',
            'label'         => __('Harmonized System Code', 'dpdconnect'),
            'placeholder'   => __('e.g.: 9030', 'dpdconnect'),
            'value'         => $product_object->get_meta('dpd_hs_code'),
            'description'   => __('Harmonized System Codes, to be used in customs data', 'dpdconnect'),
            'desc_tip'      => true,
        ];
        $fields[] = [
            'id'            => 'dpd_customs_value',
            'label'         => __('Customs Value', 'dpdconnect'),
            'placeholder'   => __('e.g.: 29.50', 'dpdconnect'),
            'value'         => $product_object->get_meta('dpd_customs_value'),
            'description'   => __('Value used by customs to determine the import duty', 'dpdconnect'),
            'desc_tip'      => true,
        ];
        $fields[] = [
            'id'            => 'dpd_origin_country',
            'label'         => __('Country of Origin', 'dpdconnect'),
            'placeholder'   => __('e.g.: NL', 'dpdconnect'),
            'value'         => $product_object->get_meta('dpd_origin_country'),
            'description'   => __('ISO 3166-1 alpha-2 code of the Country of origin', 'dpdconnect'),
            'desc_tip'      => true,
        ];
        $fields[] = [
            'id'            => 'dpd_age_check',
            'label'         => __('Age check', 'dpdconnect'),
            'type'          => 'checkbox',
            'value'         => $product_object->get_meta('dpd_age_check'),
            'description'   => __('Age check for 18+ products', 'dpdconnect'),
            'desc_tip'      => true,
        ];
        $fields[] = [
            'id'          => 'dpd_shipping_product',
            'label'       => __('Shipping product', 'dpdconnect'),
            'type'        => 'select',
            'value'       => $product_object->get_meta('dpd_shipping_product'),
            'options'     => [
                TypeHelper::DPD_SHIPPING_PRODUCT_DEFAULT => __('Default', 'dpdconnect'),
                TypeHelper::DPD_SHIPPING_PRODUCT_FRESH => __('Fresh', 'dpdconnect'),
                TypeHelper::DPD_SHIPPING_PRODUCT_FREEZE => __('Freeze', 'dpdconnect'),
            ]
        ];
        $fields[] = [
            'id'          => 'dpd_carrier_description',
            'label'       => __('Carrier description', 'dpdconnect'),
            'type'        => 'textarea',
            'value'       => $product_object->get_meta('dpd_carrier_description'),
        ];

        foreach ($fields as $field) {
            if (!array_key_exists('type', $field)) {
                woocommerce_wp_text_input($field);
                continue;
            }

            switch ($field['type']) {
                case 'checkbox':
                    woocommerce_wp_checkbox($field);
                    break;
                case 'select':
                    woocommerce_wp_select($field);
                    break;
                case 'textarea':
                    woocommerce_wp_textarea_input($field);
                    break;
            }
        }
    }

    public static function save($product_id)
    {
        if ($_POST['dpd_carrier_description'] == "" && in_array($_POST['dpd_shipping_product'], array('freeze', 'fresh'))) {
            return false;
        }

        if (!(isset(
            $_POST['woocommerce_meta_nonce'],
            $_POST['dpd_hs_code'],
            $_POST['dpd_customs_value'],
            $_POST['dpd_origin_country'],
            $_POST['dpd_shipping_product'],
            $_POST['dpd_carrier_description']
        ))) {
            Notice::add(__('Reponse could not be parsed. Please contact customerit'), NoticeType::ERROR);
            return false;
        }

        if (!wp_verify_nonce(sanitize_key($_POST['woocommerce_meta_nonce']), 'woocommerce_save_data')) {
            return false;
        }

        $order = wc_get_product($product_id);
        $order->update_meta_data('dpd_hs_code', sanitize_text_field($_POST['dpd_hs_code']));
        $order->update_meta_data('dpd_customs_value', sanitize_text_field($_POST['dpd_customs_value']));
        $order->update_meta_data('dpd_origin_country', sanitize_text_field($_POST['dpd_origin_country']));

        if (!empty($_POST['dpd_age_check'])) {
            $order->update_meta_data('dpd_age_check', sanitize_text_field($_POST['dpd_age_check']));
        } else {
            $order->delete_meta_data('dpd_age_check');
        }

        $order->update_meta_data('dpd_shipping_product', sanitize_text_field($_POST['dpd_shipping_product']));
        $order->update_meta_data('dpd_carrier_description', sanitize_text_field($_POST['dpd_carrier_description']));
        $order->save();

        return true;
    }




}
