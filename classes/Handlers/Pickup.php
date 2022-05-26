<?php

namespace DpdConnect\classes\Handlers;

use DpdConnect\classes\Option;
use DpdConnect\classes\shippingmethods\DPDShippingMethod;
use DpdConnect\Sdk\Client;
use DpdConnect\Sdk\Common\HttpClient;
use DpdConnect\Sdk\Resources\Token;

class Pickup
{

    public static function handle()
    {
        add_action('wp_enqueue_scripts', [self::class, 'registerScripts']);
        add_action('woocommerce_review_order_after_order_total', [self::class, 'addColumn']);
        add_action('wp_ajax_select_parcelshop', [self::class, 'selectParcelshop']);
        add_action('wp_ajax_nopriv_select_parcelshop', [self::class, 'selectParcelshop']);
        add_action('woocommerce_checkout_process', [self::class, 'validate']);
        add_action('woocommerce_checkout_create_order', [self::class, 'storeParcelshopId'], 20, 2);
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
        global $woocommerce;
        global $post;

        $shipping_method    = $woocommerce->session->get('chosen_shipping_methods');
        $selected_shipping_method = explode(":", $shipping_method[0]);

        $display = 'none';

        $dpdShippingMethod = new DPDShippingMethod($selected_shipping_method);

        if ($dpdShippingMethod->is_dpd_pickup) {
            $display = 'table-row';
        }

        ?>
        <tr class="dpdCheckoutRow" style="display:<?= $display ?>">
            <td colspan="2">
                <h3><?= __('Parcel Shop', 'dpdconnect'); ?></h3>
                <div class="parcelshopContainer">
                    <a id="parcelshopButton" href="#" class="openDPDParcelMap button alt"><?= __('Choose your DPD Parcel Shop', 'dpdconnect') ?></a>

                    <div id="dpd-connect-selected-container" style="display: none; margin-top: 10px;">
                        Geselecteerde parcelshop:<br />
                        <strong>%%company%%</strong><br />
                        %%street%% %%houseNo%%<br />
                        %%zipCode%% %%city%%<br />
                        <a href="#" onclick="showModal()">Veranderen</a>
                    </div>
                </div>

                <div id="parcelshopModal" class="parcelshop-modal">
                    <div class="parcelshop-modal-content">
                        <span class="parcelshop-modal-close">&times;</span>

                        <div id="dpd-connect-map-container" style="width: 100%; height: 700px; display: none;"></div>
                    </div>
                </div>

                <script>
                    var token = '<?php
                        $token = new Token(new HttpClient(Client::ENDPOINT));
                        echo $token->getPublicJWTToken(
                            Option::connectUsername(),
                            Option::connectPassword()
                        );
                    ?>';
                    var address = '<?php
                        global $woocommerce;

                        /** @var \WooCommerce $woocommerce */
                        echo $woocommerce->customer->get_shipping_address() . ' ' . $woocommerce->customer->get_shipping_postcode() . ' ' . $woocommerce->customer->get_shipping_country();

                    ?>';
                    var useGoogleMapsKey = '<?php
                        echo json_encode(Option::useDpdGoogleMapsKey());
                    ?>';

                    DPDConnect.onParcelshopSelected = function (parcelshop) {
                        closeModal();

                        // Store selected parcelshop
                        jQuery.ajax({
                            type: "post",
                            dataType: "json",
                            url: "<?php echo admin_url('admin-ajax.php') ?>",
                            data : {
                                action: "select_parcelshop",
                                nonce: "<?php echo wp_create_nonce('select_parcelshop_nonce') ?>",
                                postId: "<?php echo $post->ID ?>",
                                parcelshopId: parcelshop.parcelShopId,
                            }
                        })
                    }

                    var modal = document.getElementById("parcelshopModal");
                    var btn = document.getElementById("parcelshopButton");
                    var span = document.getElementsByClassName("parcelshop-modal-close")[0];


                    // Open the modal when button is clicked
                    btn.onclick = function() {
                        showModal();
                    }
                    // Close the modal when (x) is clicked
                    span.onclick = function() {
                        closeModal();
                    }
                    // When the user clicks anywhere outside of the modal, close it
                    window.onclick = function(event) {
                        if (event.target == modal) {
                            closeModal();
                        }
                    }

                    var scrollTop;
                    function showModal() {
                        scrollTop = document.documentElement.scrollTop;
                        modal.style.display = "block";

                        if (useGoogleMapsKey) {
                            DPDConnect.show(token, address, 'nl', '<?php echo Option::googleMapsApiKey() ?>');
                        } else {

                            DPDConnect.show(token, address, 'nl');
                        }
                    }

                    function closeModal() {
                        modal.style.display = "none";
                        document.documentElement.scrollTop = scrollTop;
                    }
                </script>
            </td>
        </tr>
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

    public static function validate()
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
