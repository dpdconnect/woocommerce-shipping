<?php

namespace DpdConnect\classes\Handlers;

use DpdConnect\classes\Option;
use DpdConnect\classes\ParcelShopFinder;

class Pickup
{
    public static function handle()
    {
        add_action('wp_enqueue_scripts', [self::class, 'registerScripts']);
        add_action('woocommerce_review_order_after_order_total', [self::class, 'addColumn']);
        add_action('wp_ajax_nopriv_dpdconnect_pickup_data', [self::class, 'updateCoordinates']);
        add_action('wp_ajax_dpdconnect_pickup_data', [self::class, 'updateCoordinates']);
        add_action('wp_ajax_select_parcelshop', [self::class, 'selectParcelshop']);
        add_action('wp_ajax_nopriv_select_parcelshop', [self::class, 'selectParcelshop']);
        add_action('woocommerce_checkout_process', [self::class, 'validate']);
        add_action('woocommerce_checkout_create_order', [self::class, 'storeParcelshopId'], 20, 2);
    }

    public static function registerScripts()
    {
        wp_register_script('dpdconnect-gmap', plugins_url('../../assets/js/dpdconnect-gmap.js', __FILE__), ['jquery'], '1.0.1', true);
        if (is_checkout()) {
            wp_enqueue_script('dpdconnect-service-google-api', 'https://maps.googleapis.com/maps/api/js?key=' . Option::googleMapsApiClientKey());
            wp_enqueue_script('dpdconnect-gmap');
        }
    }

    public static function addColumn()
    {
        global $woocommerce;
        global $post;

        $shipping_method    = $woocommerce->session->get('chosen_shipping_methods');
        $selected_shipping_method = explode(":", $shipping_method[0]);
        $translations = [
           'shipTo' => __('Select parcelshop', 'dpdconnect'),
           'changeTo' => __('Change parcelshop', 'dpdconnect'),
           'close' => __('Close', 'dpdconnect'),
           'closed' => __('Closed', 'dpdconnect'),
        ];
        wp_localize_script('dpdconnect-gmap', 'translations', $translations);
        wp_localize_script('dpdconnect-gmap', 'hook', [
            'url' => admin_url('admin-ajax.php'),
            'postId' => $post->ID,
            'nonce' => wp_create_nonce('select_parcelshop_nonce'),
        ]);

        $display = 'none';
        if ($selected_shipping_method[0] === 'dpd_pickup') {
            $display = 'table-row';
        }

        ?>
        <tr class="dpdCheckoutRow" style="display:<?= $display ?>">
            <td colspan="2">
                <h3><?= __('Parcel Shop', 'dpdconnect'); ?></h3>
                <div class="mapContainer"></div>
                <div class="parcel-notice"></div>
                <a href="#" class="openDPDParcelMap button alt"><?= __('Choose your DPD Parcel Shop', 'dpdconnect') ?></a>
                <div id="selectedParcelShop"/>
            </td>
        </tr>
        <input name="parcel-id" type="hidden" id="parcel-id"/>
        <?php
    }

    public static function updateCoordinates()
    {
        global $woocommerce;

        $shipping_method    = $woocommerce->session->get('chosen_shipping_methods');
        $selected_shipping_method = explode(":", $shipping_method[0]);

        if ($selected_shipping_method[0] !== 'dpd_pickup') {
            wp_die();
        }

        $postcode = $woocommerce->customer->get_billing_postcode();
        $isocode = $woocommerce->customer->get_billing_country();

        if (!$postcode || ! $isocode) {
            wp_send_json_error(__('Your address is needed to search for nearby parcelshops.'));
        }

        $parcelshopFinder = new ParcelShopFinder();
        $coordinates = $parcelshopFinder->getGeoData($postcode, $isocode);
        $parcelshops = $parcelshopFinder->getParcelShops($coordinates, $isocode);

        //Make openingHours translateable
        foreach ($parcelshops as $key => $parcelshop) {
            foreach($parcelshop['openingHours'] as $openingHourKey => $openingHour) {
                switch ($openingHour['weekday']) {
                    case 'maandag':
                        $parcelshops[$key]['openingHours'][$openingHourKey]['weekday'] = __('monday', 'dpdconnect');
                        break;
                    case 'dinsdag':
                        $parcelshops[$key]['openingHours'][$openingHourKey]['weekday'] = __('tuesday', 'dpdconnect');
                        break;
                    case 'woensdag':
                        $parcelshops[$key]['openingHours'][$openingHourKey]['weekday'] = __('wednesday', 'dpdconnect');
                        break;
                    case 'donderdag':
                        $parcelshops[$key]['openingHours'][$openingHourKey]['weekday'] = __('thursday', 'dpdconnect');
                        break;
                    case 'vrijdag':
                        $parcelshops[$key]['openingHours'][$openingHourKey]['weekday'] = __('friday', 'dpdconnect');
                        break;
                    case 'zaterdag':
                        $parcelshops[$key]['openingHours'][$openingHourKey]['weekday'] = __('saturday', 'dpdconnect');
                        break;
                    case 'zondag':
                        $parcelshops[$key]['openingHours'][$openingHourKey]['weekday'] = __('sunday', 'dpdconnect');
                        break;
                }
            }
        }

        if (!is_array($parcelshops)) {
            wp_send_json_error(__('Could not find parcelshops near your address.'));
        }

        echo json_encode([
            'coordinates' => $coordinates,
            'parcelshops' => $parcelshops
        ]);

        wp_die();
    }

    public static function selectParcelShop()
    {
        if (!wp_verify_nonce($_REQUEST['nonce'], "select_parcelshop_nonce")) {
            exit("Permission denied");
        }

        $parcelshopId = $_REQUEST["parcelshopId"];

        WC()->session->set('dpd_order_metadata', ['parcelshop_id' => $parcelshopId]);

        exit();
    }

    public static function validate()
    {
        global $woocommerce;

        $shippingMethod = $woocommerce->session->get('chosen_shipping_methods');
        $selectedShippingMethod = explode(":", $shippingMethod[0]);

        if ($selectedShippingMethod[0] !== 'dpd_pickup') {
            return; // No validation for parcelshop is needed
        }

        $validationError = __('Please select a parcelshop.');

        if (is_null(WC()->session->get('dpd_order_metadata'))) {
            wc_add_notice($validationError, 'error');
            return;
        };

        if (!isset(WC()->session->get('dpd_order_metadata')['parcelshop_id'])) {
            wc_add_notice($validationError, 'error');
            return;
        }

        return;
    }

    public static function storeParcelshopId($order, $data)
    {
        $dpdOrderMetaData = WC()->session->get('dpd_order_metadata');
        $parcelshopId = $dpdOrderMetaData['parcelshop_id'];
        $order->update_meta_data('_dpd_parcelshop_id', $parcelshopId);
        WC()->session->__unset('dpd_order_metadata');
    }
}
