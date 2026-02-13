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
        // Classic checkout validation
        add_action('woocommerce_checkout_process', [self::class, 'validateParcelshopSelection']);
        // Block checkout validation and storage
        add_action('woocommerce_store_api_checkout_update_order_from_request', [self::class, 'storeParcelshopId'], 20, 2);
    }

    public static function registerScripts()
    {
        wp_register_script('dpd-connect-map', Client::ENDPOINT . '/parcelshop/map/js', array('jquery'), false, false);

        if (is_checkout()) {
            wp_enqueue_script('dpd-connect-map');
        }
    }

    /**
     * Generate public JWT token server-side.
     * Credentials are handled in isolated PHP context, only the token is returned.
     */
    private static function getPublicToken(): string
    {
        $token = new Token(new HttpClient(Client::ENDPOINT));
        $token->setCacheWrapper(new CacheWrapper(new Cache()));
        return $token->getPublicJWTToken(
            Option::connectUsername(),
            Option::connectPassword()
        );
    }

    /**
     * Get all DPD pickup method IDs (only parcelshop types, not all DPD methods).
     * Returns an array of method IDs like ['dpd_shipping_method:123', 'dpd_shipping_method:456']
     */
    private static function getDpdPickupMethodIds(): array
    {
        $pickupMethodIds = [];

        // Get all shipping zones
        $zones = \WC_Shipping_Zones::get_zones();

        foreach ($zones as $zone) {
            $zone_obj = new \WC_Shipping_Zone($zone['id']);
            $shipping_methods = $zone_obj->get_shipping_methods();

            foreach ($shipping_methods as $instance_id => $shipping_method) {
                // Only check DPD shipping methods
                if ($shipping_method->id === 'dpd_shipping_method') {
                    // Check if this is a pickup/parcelshop method
                    if (isset($shipping_method->is_dpd_pickup) && $shipping_method->is_dpd_pickup) {
                        $method_id = $shipping_method->id . ':' . $instance_id;
                        $pickupMethodIds[] = $method_id;
                    }
                }
            }
        }

        // Also check "Rest of the World" zone (zone_id = 0)
        $zone_0 = new \WC_Shipping_Zone(0);
        $shipping_methods = $zone_0->get_shipping_methods();

        foreach ($shipping_methods as $instance_id => $shipping_method) {
            if ($shipping_method->id === 'dpd_shipping_method') {
                if (isset($shipping_method->is_dpd_pickup) && $shipping_method->is_dpd_pickup) {
                    $method_id = $shipping_method->id . ':' . $instance_id;
                    $pickupMethodIds[] = $method_id;
                }
            }
        }

        return $pickupMethodIds;
    }

    /**
     * Check if the currently selected shipping method is a parcelshop type.
     * Supports both DPD pickup methods and additional parcelshop methods.
     */
    private static function isParcelshopShippingSelected(): bool
    {
        $shippingMethod = WC()->session->get('chosen_shipping_methods');
        if (empty($shippingMethod) || !isset($shippingMethod[0])) {
            return false;
        }

        $selectedShippingMethodFull = $shippingMethod[0];
        $selectedShippingMethod = explode(":", $selectedShippingMethodFull);
        $dpdShippingMethod = new DPDShippingMethod($selectedShippingMethod);

        // Check if it's a DPD pickup method
        if ($dpdShippingMethod->is_dpd_pickup) {
            return true;
        }

        // Check if it's one of the additional parcelshop methods
        $additionalMethods = Option::additionalParcelshopMethods();
        if (!empty($additionalMethods)) {
            foreach ($additionalMethods as $additionalMethod) {
                if ($selectedShippingMethodFull === $additionalMethod ||
                    strpos($selectedShippingMethodFull, $additionalMethod) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a parcelshop has been selected in session.
     */
    private static function hasParcelshopSelected(): bool
    {
        $metadata = WC()->session->get('dpd_order_metadata');
        return !empty($metadata) && !empty($metadata['parcelshop_id']);
    }

    /**
     * Validate parcelshop selection for classic checkout.
     * Hooked to woocommerce_checkout_process.
     */
    public static function validateParcelshopSelection()
    {
        if (!self::isParcelshopShippingSelected()) {
            return;
        }

        if (!self::hasParcelshopSelected()) {
            wc_add_notice(__('Please select a parcelshop before placing your order.', 'dpdconnect'), 'error');
        }
    }

    public static function addColumn()
    {
        if (!is_checkout()) {
            return;
        }
        global $post;

        // Generate token server-side before any output
        $publicToken = self::getPublicToken();
        $useGoogleMapsKey = Option::useDpdGoogleMapsKey();
        $googleMapsApiKey = Option::googleMapsApiKey();
        $ajaxUrl = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('select_parcelshop_nonce');
        $postId = $post->ID;
        $additionalParcelshopMethods = Option::additionalParcelshopMethods();

        // Get list of DPD pickup method IDs
        $dpdPickupMethods = self::getDpdPickupMethodIds();
    ?>
        <script>
            jQuery(document).ready(function() {
                let dpdBarInitialized = false;
                let dpdConnectReady = false;
                let parcelshopSelected = false;
                let selectedParcelshopData = null;

                // Additional parcelshop methods configured in settings
                const additionalParcelshopMethods = <?= json_encode($additionalParcelshopMethods) ?>;

                // DPD pickup method IDs (only pickup/parcelshop types)
                const dpdPickupMethodIds = <?= json_encode($dpdPickupMethods) ?>;

                // Wait for DPDConnect to be available
                function waitForDPDConnect(callback, maxWait = 10000) {
                    const startTime = Date.now();
                    const checkInterval = setInterval(function() {
                        if (typeof DPDConnect !== 'undefined') {
                            clearInterval(checkInterval);
                            dpdConnectReady = true;
                            console.log('DPD Parcelshop: DPDConnect library loaded successfully');
                            callback();
                        } else if (Date.now() - startTime > maxWait) {
                            clearInterval(checkInterval);
                            console.error('DPD Parcelshop: DPDConnect library failed to load within ' + (maxWait/1000) + ' seconds');
                        }
                    }, 100);
                }

                function init() {
                    createDPDBar();
                    checkAndShowBar();

                    // Wait for DPDConnect to load before setting up event listeners
                    waitForDPDConnect(function() {
                        setupEventListeners();
                    });
                }

                function createDPDBar() {
                    // Only create once
                    if (jQuery('#dpdCheckout').length > 0) {
                        return;
                    }

                    const $element = jQuery(`
                        <div id="dpdCheckout" class="dpd-fixed-bottom-bar" style="display: none;">
                            <button id="dpdCollapseToggle" class="dpd-collapse-toggle" type="button" aria-label="Toggle parcelshop bar">
                                <svg class="dpd-arrow-down" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                                <svg class="dpd-arrow-up" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="18 15 12 9 6 15"></polyline>
                                </svg>
                            </button>

                            <div class="dpd-bar-content">
                                <div class="dpd-parcelshop-slideup-bar">
                                    <div class="dpd-slideup-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                        </svg>
                                    </div>
                                    <div class="dpd-slideup-text">
                                        <?= esc_html__('Choose your DPD pickup point', 'dpdconnect') ?>
                                    </div>
                                    <div class="dpd-slideup-button-wrapper">
                                        <button id="parcelshopButton" type="button" class="dpd-slideup-button"><?= esc_html__('Select DPD Parcel Shop', 'dpdconnect') ?></button>
                                    </div>
                                </div>

                                <div id="dpd-connect-selected-container" style="display: none; padding: 10px 20px; background: #f0f7ff; border-top: 1px solid #e0e0e0;">
                                    Geselecteerde parcelshop:<br />
                                    <strong>%%company%%</strong><br />
                                    %%street%% %%houseNo%%<br />
                                    %%zipCode%% %%city%%<br />
                                </div>
                            </div>
                        </div>

                        <div id="parcelshopModal" class="parcelshop-modal">
                            <div class="parcelshop-modal-content">
                                <span class="parcelshop-modal-close">&times;</span>
                                <div id="dpd-connect-map-container" style="width: 100%; height: 700px;"></div>
                            </div>
                        </div>
                    `);

                    jQuery('body').append($element);
                    dpdBarInitialized = true;
                    console.log('DPD Parcelshop: Bar created and appended to body');
                }

                function checkAndShowBar() {
                    const isDpdMethodSelected = isParcelshopMethodSelected();

                    if (isDpdMethodSelected) {
                        jQuery('#dpdCheckout').addClass('dpd-bar-visible').slideDown(300);
                    } else {
                        jQuery('#dpdCheckout').removeClass('dpd-bar-visible').slideUp(300);
                    }
                }

                function isParcelshopMethodSelected() {
                    let selectedMethodValue = null;

                    // Check WooCommerce block checkout
                    const $blockShippingMethod = jQuery('.wc-block-components-shipping-rates-control input:checked');
                    if ($blockShippingMethod.length > 0) {
                        selectedMethodValue = $blockShippingMethod.val();
                    }

                    // Check classic checkout
                    if (!selectedMethodValue) {
                        const $classicShippingMethod = jQuery('input[name^="shipping_method"]:checked');
                        if ($classicShippingMethod.length > 0) {
                            selectedMethodValue = $classicShippingMethod.val();
                        }
                    }

                    if (!selectedMethodValue) {
                        return false;
                    }

                    // Check if it's a DPD pickup method (only parcelshop types, not all DPD methods)
                    if (dpdPickupMethodIds && dpdPickupMethodIds.length > 0) {
                        for (let i = 0; i < dpdPickupMethodIds.length; i++) {
                            if (selectedMethodValue === dpdPickupMethodIds[i]) {
                                return true;
                            }
                        }
                    }

                    // Check if it's one of the additional parcelshop methods
                    if (additionalParcelshopMethods && additionalParcelshopMethods.length > 0) {
                        for (let i = 0; i < additionalParcelshopMethods.length; i++) {
                            // Check if the selected method matches exactly
                            if (selectedMethodValue === additionalParcelshopMethods[i]) {
                                return true;
                            }
                        }
                    }

                    return false;
                }

                function setupEventListeners() {
                    const token = '<?= esc_js($publicToken) ?>';
                    const useGoogleMapsKey = <?= $useGoogleMapsKey ? 'true' : 'false' ?>;

                    // Handle parcelshop selection
                    DPDConnect.onParcelshopSelected = function(parcelshop) {
                        closeModal();

                        jQuery.ajax({
                            type: 'post',
                            dataType: 'json',
                            url: '<?= esc_url($ajaxUrl) ?>',
                            data : {
                                action: 'select_parcelshop',
                                nonce: '<?= esc_js($nonce) ?>',
                                postId: '<?= intval($postId) ?>',
                                parcelshopId: parcelshop.parcelShopId,
                            },
                            success: function(response) {
                                if (response.success) {
                                    parcelshopSelected = true;
                                    selectedParcelshopData = parcelshop;
                                    updateParcelshopUI(parcelshop);
                                    console.log('DPD Parcelshop: Selection saved successfully');
                                }
                            },
                            error: function() {
                                console.error('DPD Parcelshop: Failed to save selection');
                                alert('<?= esc_js(__('Failed to save parcelshop selection. Please try again.', 'dpdconnect')) ?>');
                            }
                        });
                    }

                    // Update UI after parcelshop selection
                    function updateParcelshopUI(parcelshop) {
                        const $button = jQuery('#parcelshopButton');
                        const $selectedContainer = jQuery('#dpd-connect-selected-container');

                        // Update button to show change option
                        $button.text('<?= esc_js(__('Change Parcel Shop', 'dpdconnect')) ?>');
                        $button.addClass('dpd-parcelshop-selected');

                        // Show selected parcelshop info
                        if ($selectedContainer.length && parcelshop) {
                            let html = $selectedContainer.html();
                            html = html.replace('%%company%%', parcelshop.company || '');
                            html = html.replace('%%street%%', parcelshop.street || '');
                            html = html.replace('%%houseNo%%', parcelshop.houseNo || '');
                            html = html.replace('%%zipCode%%', parcelshop.zipCode || '');
                            html = html.replace('%%city%%', parcelshop.city || '');
                            $selectedContainer.html(html).show();
                        }
                    }

                    // Collapse/Expand toggle
                    jQuery(document).on('click', '#dpdCollapseToggle', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('DPD Parcelshop: Toggle clicked');

                        const $bar = jQuery('#dpdCheckout');
                        const isCollapsed = $bar.hasClass('dpd-bar-collapsed');

                        if (isCollapsed) {
                            $bar.removeClass('dpd-bar-collapsed');
                            console.log('DPD Parcelshop: Bar expanded');
                        } else {
                            $bar.addClass('dpd-bar-collapsed');
                            console.log('DPD Parcelshop: Bar collapsed');
                        }
                    });

                    // Button click
                    jQuery(document).on('click', '#parcelshopButton', function(e) {
                        e.preventDefault();
                        console.log('DPD Parcelshop: Button clicked');

                        if (!dpdConnectReady) {
                            console.warn('DPD Parcelshop: DPDConnect not ready yet, waiting...');
                            waitForDPDConnect(function() {
                                showModal(useGoogleMapsKey, token);
                            });
                        } else {
                            showModal(useGoogleMapsKey, token);
                        }
                    });

                    // Modal close
                    jQuery(document).on('click', '.parcelshop-modal-close', function() {
                        closeModal();
                    });

                    jQuery(document).on('click', function(event) {
                        if (event.target === jQuery('#parcelshopModal').get(0)) {
                            closeModal();
                        }
                    });

                    // Watch for shipping method changes (Block checkout)
                    jQuery(document).on('change', '.wc-block-components-shipping-rates-control input', function() {
                        checkAndShowBar();
                    });

                    // Watch for shipping method changes (Classic checkout)
                    jQuery(document).on('change', 'input[name^="shipping_method"]', function() {
                        checkAndShowBar();
                    });

                    // Watch for address changes
                    jQuery(document).on('change', '#shipping-postcode, #shipping-address_1, #shipping-country, #billing-postcode, #billing-address_1, #billing-country', function() {
                        setTimeout(checkAndShowBar, 500);
                    });

                    // Watch for WooCommerce block updates
                    jQuery(document).on('updated_checkout', function() {
                        checkAndShowBar();
                    });

                    // Use MutationObserver for efficient DOM change detection
                    const observer = new MutationObserver(function(mutations) {
                        let shouldCheck = false;
                        mutations.forEach(function(mutation) {
                            // Check if shipping-related elements were added or modified
                            if (mutation.type === 'childList' || mutation.type === 'attributes') {
                                const target = mutation.target;
                                if (target.classList && (
                                    target.classList.contains('wc-block-components-shipping-rates-control') ||
                                    target.classList.contains('woocommerce-shipping-methods') ||
                                    target.id === 'shipping_method' ||
                                    target.closest && (target.closest('.wc-block-components-shipping-rates-control') || target.closest('#shipping_method'))
                                )) {
                                    shouldCheck = true;
                                }
                            }
                        });
                        if (shouldCheck) {
                            if (!dpdBarInitialized) {
                                createDPDBar();
                            }
                            checkAndShowBar();
                        }
                    });

                    // Observe the checkout form for shipping method changes
                    const checkoutForm = document.querySelector('.woocommerce-checkout, .wp-block-woocommerce-checkout');
                    if (checkoutForm) {
                        observer.observe(checkoutForm, {
                            childList: true,
                            subtree: true,
                            attributes: true,
                            attributeFilter: ['checked', 'class']
                        });
                    }

                    // Fallback: also observe body for dynamically loaded checkout forms
                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });

                    // Classic checkout form validation
                    jQuery('form.checkout').on('checkout_place_order', function() {
                        if (isParcelshopMethodSelected() && !parcelshopSelected) {
                            // Scroll to and highlight the DPD bar
                            jQuery('#dpdCheckout').addClass('dpd-validation-error');
                            jQuery('html, body').animate({
                                scrollTop: jQuery('#dpdCheckout').offset().top - 100
                            }, 500);

                            // Show error and prevent submission
                            if (jQuery('.dpd-parcelshop-error').length === 0) {
                                jQuery('#dpdCheckout .dpd-bar-content').prepend(
                                    '<div class="dpd-parcelshop-error woocommerce-error" style="background:#ffebe9;color:#cc1818;padding:10px 15px;margin-bottom:10px;border-left:4px solid #cc1818;">' +
                                    '<?= esc_js(__('Please select a parcelshop before placing your order.', 'dpdconnect')) ?>' +
                                    '</div>'
                                );
                            }

                            setTimeout(function() {
                                jQuery('#dpdCheckout').removeClass('dpd-validation-error');
                            }, 3000);

                            return false;
                        }
                        return true;
                    });

                    // Block checkout validation - intercept before submission
                    jQuery(document).on('click', '.wc-block-components-checkout-place-order-button, .wp-block-woocommerce-checkout-actions-block button', function(e) {
                        if (isParcelshopMethodSelected() && !parcelshopSelected) {
                            e.preventDefault();
                            e.stopPropagation();

                            // Scroll to and highlight the DPD bar
                            jQuery('#dpdCheckout').addClass('dpd-validation-error');
                            jQuery('html, body').animate({
                                scrollTop: jQuery('#dpdCheckout').offset().top - 100
                            }, 500);

                            // Show error message
                            if (jQuery('.dpd-parcelshop-error').length === 0) {
                                jQuery('#dpdCheckout .dpd-bar-content').prepend(
                                    '<div class="dpd-parcelshop-error" style="background:#ffebe9;color:#cc1818;padding:10px 15px;margin-bottom:10px;border-left:4px solid #cc1818;">' +
                                    '<?= esc_js(__('Please select a parcelshop before placing your order.', 'dpdconnect')) ?>' +
                                    '</div>'
                                );
                            }

                            setTimeout(function() {
                                jQuery('#dpdCheckout').removeClass('dpd-validation-error');
                                jQuery('.dpd-parcelshop-error').fadeOut(function() {
                                    jQuery(this).remove();
                                });
                            }, 5000);

                            return false;
                        }
                    });

                    // Reset parcelshop selection when shipping method changes away from parcelshop
                    jQuery(document).on('change', '.wc-block-components-shipping-rates-control input, input[name^="shipping_method"]', function() {
                        if (!isParcelshopMethodSelected()) {
                            // Clear error messages when switching away from parcelshop
                            jQuery('.dpd-parcelshop-error').remove();
                            jQuery('#dpdCheckout').removeClass('dpd-validation-error');
                        }
                    });
                }

                function showModal(useGoogleMapsKey, token) {
                    // Check if DPDConnect is available
                    if (typeof DPDConnect === 'undefined') {
                        console.error('DPD Parcelshop library is not loaded');
                        alert('Er is een probleem met het laden van de parcelshop selector. Probeer de pagina te verversen.');
                        return;
                    }

                    console.log('DPD Parcelshop: showModal called');

                    // Show modal
                    jQuery('#parcelshopModal').show();

                    let address = '';

                    // Try to get address from various input fields
                    if (jQuery('#shipping-address_1').length > 0) {
                        address = jQuery('#shipping-address_1').val() + ' ' + jQuery('#shipping-postcode').val() + ' ' + jQuery('#shipping-country').val();
                    }
                    else if (jQuery('#billing-address_1').length > 0) {
                        address = jQuery('#billing-address_1').val() + ' ' + jQuery('#billing-postcode').val() + ' ' + jQuery('#billing-country').val();
                    }
                    else {
                        // Fallback for other field structures
                        const addressField = jQuery('input[id*="address"], input[name*="address"]').first().val() || '';
                        const postcodeField = jQuery('input[id*="postcode"], input[name*="postcode"]').first().val() || '';
                        const countryField = jQuery('select[id*="country"], select[name*="country"]').first().val() || 'NL';
                        address = addressField + ' ' + postcodeField + ' ' + countryField;
                    }

                    console.log('DPD Parcelshop: Opening modal with address:', address);
                    console.log('DPD Parcelshop: Token:', token ? 'Available' : 'Missing');
                    console.log('DPD Parcelshop: useGoogleMapsKey:', useGoogleMapsKey);

                    try {
                        const mapContainer = document.getElementById('dpd-connect-map-container');
                        console.log('DPD Parcelshop: Map container found:', mapContainer ? 'Yes' : 'No');

                        if (useGoogleMapsKey) {
                            console.log('DPD Parcelshop: Calling DPDConnect.show with Google Maps key');
                            DPDConnect.show(token, address, 'nl', '<?= esc_js($googleMapsApiKey) ?>');
                        }
                        else {
                            console.log('DPD Parcelshop: Calling DPDConnect.show without Google Maps key');
                            DPDConnect.show(token, address, 'nl');
                        }
                    } catch (error) {
                        console.error('DPD Parcelshop error:', error);
                        closeModal();
                        alert('Er is een fout opgetreden bij het openen van de parcelshop selector.\n\nMogelijke oorzaken:\n- Ad-blocker blokkeert Google Maps\n- Netwerkproblemen\n\nProbeer:\n1. Ad-blocker uit te schakelen\n2. De pagina te verversen\n3. Een ander adres in te vullen');
                    }
                }

                function closeModal() {
                    console.log('DPD Parcelshop: Closing modal');
                    jQuery('#parcelshopModal').hide();
                }

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

        $parcelshopId = isset($_REQUEST["parcelshopId"]) ? sanitize_text_field($_REQUEST["parcelshopId"]) : '';

        // Validate parcelshopId format (alphanumeric with possible hyphens/underscores)
        if (empty($parcelshopId) || !preg_match('/^[a-zA-Z0-9_-]+$/', $parcelshopId)) {
            wp_send_json_error(['message' => 'Invalid parcelshop ID format'], 400);
            exit();
        }

        WC()->session->set('dpd_order_metadata', ['parcelshop_id' => $parcelshopId]);

        wp_send_json_success();
        exit();
    }

    /**
     * Validate and store parcelshop ID for block checkout (Store API).
     * Throws exception to stop checkout if validation fails.
     */
    public static function storeParcelshopId($order, $data)
    {
        if (!self::isParcelshopShippingSelected()) {
            return;
        }

        $validationError = __('Please select a parcelshop before placing your order.', 'dpdconnect');

        if (!self::hasParcelshopSelected()) {
            // Throw exception to stop block checkout - Store API will catch and return error
            throw new \Exception($validationError);
        }

        $dpdOrderMetaData = WC()->session->get('dpd_order_metadata');
        $parcelshopId = $dpdOrderMetaData['parcelshop_id'];
        $order->update_meta_data('_dpd_parcelshop_id', $parcelshopId);
        $order->save();
        WC()->session->__unset('dpd_order_metadata');
    }
}
