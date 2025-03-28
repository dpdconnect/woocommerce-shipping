<?php

namespace DpdConnect\classes\Handlers;

use DpdConnect\classes\Option;
use DpdConnect\classes\shippingmethods\DPDShippingMethod;
use DpdConnect\Sdk\CacheWrapper;
use DpdConnect\Sdk\Client;
use DpdConnect\Sdk\Common\HttpClient;
use DpdConnect\Sdk\Resources\Token;
use DpdConnect\classes\Connect\Cache;

class Pickup
{

    public static function handle()
    {
        add_action('wp_enqueue_scripts', [self::class, 'registerScripts']);
        add_action('wp_head', [self::class, 'addColumn']);
        add_action('wp_ajax_select_parcelshop', [self::class, 'selectParcelshop']);
        add_action('wp_ajax_nopriv_select_parcelshop', [self::class, 'selectParcelshop']);
        add_action('woocommerce_store_api_checkout_update_order_from_request', [self::class, 'storeParcelshopId'], 20, 2);
    }

    public static function registerScripts()
    {
        wp_register_script('dpd-connect-map', Client::ENDPOINT . '/parcelshop/map/js', array('jquery'), false, false);

        if (is_checkout()) {
            wp_enqueue_script('dpd-connect-map');
        }
    }

    public static function addColumn()
    {
        if (!is_checkout()) {
            return;
        }
        global $post;
    ?>
        <script>
            jQuery(document).ready(function() {
                function init() {
                    const intervalId = setInterval(function() {
                        const $shippingOptions= jQuery('.wc-block-components-shipping-rates-control');

                        if ($shippingOptions) {
                            clearInterval(intervalId);
                            initParcelshopSelector();
                        }
                    }, 500);
                }

                function initParcelshopSelector() {
                    const $shippingOptions = jQuery('.wc-block-components-shipping-rates-control');

                    const $element = jQuery(`
                        <div id="dpdCheckout">
                            <div class="parcelshopContainer">
                                <a id="parcelshopButton" href="javascript:void(0);" class="openDPDParcelMap button alt"><?= __('Choose your DPD Parcel Shop', 'dpdconnect') ?></a>

                                <div id="dpd-connect-selected-container" style="display: none; margin-top: 10px;">
                                    Geselecteerde parcelshop:<br />
                                    <strong>%%company%%</strong><br />
                                    %%street%% %%houseNo%%<br />
                                    %%zipCode%% %%city%%<br />
                                </div>

                                <div id="parcelshopModal" class="parcelshop-modal">
                                    <div class="parcelshop-modal-content">
                                        <span class="parcelshop-modal-close">&times;</span>

                                        <div id="dpd-connect-map-container" style="width: 100%; height: 700px; display: none;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);

                    const $methods = $shippingOptions.find('input');

                    $methods.on('change', function() {
                        const $method = jQuery(this);

                        if ($method.val().includes('dpd_shipping_method')) {
                            $shippingOptions.append($element);
                        }
                        else {
                            $element.remove();
                        }
                    });

                    const $method = $shippingOptions.find('input:checked');

                    if ($method.val().includes('dpd_shipping_method')) {
                        $shippingOptions.append($element);
                    }

                    <?php
                    $token = new Token(new HttpClient(Client::ENDPOINT));
                    $token->setCacheWrapper(new CacheWrapper(new Cache()));
                    ?>

                    const token = '<?= $token->getPublicJWTToken(
                        Option::connectUsername(),
                        Option::connectPassword())
                    ?>';

                    const useGoogleMapsKey = '<?= json_encode(Option::useDpdGoogleMapsKey()) ?>';

                    DPDConnect.onParcelshopSelected = function(parcelshop) {
                        closeModal();

                        jQuery.ajax({
                            type: 'post',
                            dataType: 'json',
                            url: '<?= admin_url('admin-ajax.php') ?>',
                            data : {
                                action: 'select_parcelshop',
                                nonce: '<?= wp_create_nonce('select_parcelshop_nonce') ?>',
                                postId: '<?= $post->ID ?>',
                                parcelshopId: parcelshop.parcelShopId,
                            }
                        });
                    }

                    jQuery(document).on('click', '#parcelshopButton', function() {
                        showModal(useGoogleMapsKey, token);
                    });

                    jQuery(document).on('click', '.parcelshop-modal-close', function() {
                        closeModal();
                    });

                    jQuery(document).on('click', function(event) {
                        if (event.target === jQuery('#parcelshopModal').get(0)) {
                            closeModal();
                        }
                    });
                }

                function showModal(useGoogleMapsKey, token) {
                    jQuery('#parcelshopModal').show();

                    const address = jQuery('#shipping-address_1').val() + ' ' + jQuery('#shipping-postcode').val() + ' ' + jQuery('#shipping-country').val();

                    if (useGoogleMapsKey) {
                        DPDConnect.show(token, address, 'nl', '<?= Option::googleMapsApiKey() ?>');
                    }
                    else {
                        DPDConnect.show(token, address, 'nl');
                    }
                }

                function closeModal() {
                    jQuery('#parcelshopModal').hide();
                }

                jQuery(document).on('click', '#shipping-method [role="radio"]', function() {
                    initParcelshopSelector();
                });

                init();
            });
        </script>
        <?php
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

    public static function storeParcelshopId($order, $data)
    {
        global $woocommerce;

        $shippingMethod = $woocommerce->session->get('chosen_shipping_methods');
        $selectedShippingMethod = explode(":", $shippingMethod[0]);

        $dpdShippingMethod = new DPDShippingMethod($selectedShippingMethod);
        // Shipping method is not of type parcelshop
        if (!$dpdShippingMethod->is_dpd_pickup) {
            // No validation for parcelshop is needed
            return;
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

        $dpdOrderMetaData = WC()->session->get('dpd_order_metadata');
        $parcelshopId = $dpdOrderMetaData['parcelshop_id'];
        $order->update_meta_data('_dpd_parcelshop_id', $parcelshopId);
        $order->save();
        WC()->session->__unset('dpd_order_metadata');
    }
}
