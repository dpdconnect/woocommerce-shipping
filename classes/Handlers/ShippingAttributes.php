<?php

namespace DpdConnect\classes\Handlers;

class ShippingAttributes
{
    public static function handle()
    {
        add_action('woocommerce_product_options_shipping', [self::class, 'add']);
        add_action('woocommerce_process_product_meta_simple', [self::class, 'save']);
    }

    public static function add()
    {
        global $product_object;

        $fields[] = [
            'id'            => 'dpd_hs_code',
            'label'         => __('Harmonized System Code', 'dpdconnect'),
            'placeholder'   => __('e.g.: 9030', 'dpdconnect'),
            'value'         => get_post_meta($product_object->get_id(), 'dpd_hs_code', true),
            'description'   => __('Harmonized System Codes, to be used in customs data', 'dpdconnect'),
            'desc_tip'      => true,
        ];
        $fields[] = [
            'id'            => 'dpd_customs_value',
            'label'         => __('Customs Value', 'dpdconnect'),
            'placeholder'   => __('e.g.: 29.50', 'dpdconnect'),
            'value'         => get_post_meta($product_object->get_id(), 'dpd_customs_value', true),
            'description'   => __('Value used by customs to determine the import duty', 'dpdconnect'),
            'desc_tip'      => true,
        ];
        $fields[] = [
            'id'            => 'dpd_origin_country',
            'label'         => __('Country of Origin', 'dpdconnect'),
            'placeholder'   => __('e.g.: NL', 'dpdconnect'),
            'value'         => get_post_meta($product_object->get_id(), 'dpd_origin_country', true),
            'description'   => __('ISO 3166-1 alpha-2 code of the Country of origin', 'dpdconnect'),
            'desc_tip'      => true,
        ];

        foreach ($fields as $field) {
            woocommerce_wp_text_input($field);
        }
    }

    public static function save($post_id)
    {
        if (!(isset(
            $_POST['woocommerce_meta_nonce'],
            $_POST['dpd_hs_code'],
            $_POST['dpd_customs_value'],
            $_POST['dpd_origin_country']
        ))) {
            return false;
        }

        if (!wp_verify_nonce(sanitize_key($_POST['woocommerce_meta_nonce']), 'woocommerce_save_data')) {
            return false;
        }

        update_post_meta($post_id, 'dpd_hs_code', sanitize_text_field($_POST['dpd_hs_code']));
        update_post_meta($post_id, 'dpd_customs_value', sanitize_text_field($_POST['dpd_customs_value']));
        update_post_meta($post_id, 'dpd_origin_country', sanitize_text_field($_POST['dpd_origin_country']));
    }
}
