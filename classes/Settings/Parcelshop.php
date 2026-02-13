<?php

namespace DpdConnect\classes\Settings;

use DpdConnect\classes\Option;

class Parcelshop
{
    const PAGE = 'dpdconnect_parcelshop';
    const SECTION = 'dpdconnect_parcelshop';

    public static function handle()
    {
        add_action('admin_init', [self::class, 'render']);
    }

    public static function render()
    {
        $sectionCallback = [self::class, 'sectionCallback'];

        register_setting(self::PAGE, self::SECTION);

        add_settings_section(
            self::SECTION,
            __('Parcelshop settings', 'dpdconnect'),
            $sectionCallback,
            self::PAGE
        );

        $options = get_option('dpdconnect_parcelshop');

        add_settings_field(
            'dpdconnect_google_maps_api_key', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Google Maps API Key', 'dpdconnect'),
            [self::class, 'renderApiKeyInput'],
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_google_maps_api_key',
                'class' => isset($options['dpdconnect_use_dpd_google_maps_api_key']) ? 'hidden google-maps-api-key' : 'dpdconnect_row google-maps-api-key',
                'dpdconnect_custom_data' => 'custom',
                'type' => 'password',
                'description' => __('This key is used for rendering Google Maps in the checkout and requesting Geographical coordinates based on zipcodes.', 'dpdconnect'),
            ]
        );

        add_settings_field(
            'dpdconnect_use_dpd_google_maps_api_key', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __("Use DPD's Google Maps API Key", 'dpdconnect'),
            [self::class, 'renderUseDpdInput'],
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_use_dpd_google_maps_api_key',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
                'type' => 'checkbox',
                'description' => __('These may be subject to rate limiting, high volume users should use their own Google keys', 'dpdconnect'),
            ]
        );

        add_settings_field(
            'dpdconnect_additional_parcelshop_methods',
            __('Additional Parcelshop Shipping Methods', 'dpdconnect'),
            [self::class, 'renderAdditionalMethodsInput'],
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_additional_parcelshop_methods',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
                'type' => 'checkbox',
                'description' => __('Enable parcelshop selection for non-DPD shipping methods (e.g., Table Rate Shipping). DPD shipping methods are automatically detected and don\'t need to be selected here.', 'dpdconnect'),
            ]
        );
    }

    public static function renderApiKeyInput($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option('dpdconnect_parcelshop');
        // output the field
        ?>
        <input type="<?php echo esc_attr($args['type']); ?>"
               id="<?php echo esc_attr($args['label_for']); ?>"
               data-custom="<?php echo esc_attr($args['dpdconnect_custom_data']); ?>"
               name="dpdconnect_parcelshop[<?php echo esc_attr($args['label_for']); ?>]"
               value="<?php echo $options[ $args['label_for']] ?? '' ?>"
        />

        <input type="checkbox" onclick="showKey()"><?php echo __('Show key', 'dpdconnect') ?>
        <script>
            function showKey() {
                var keyInput = document.getElementById("<?php echo esc_attr($args['label_for']); ?>");
                if (keyInput.type === "password") {
                    keyInput.type = "text";
                } else {
                    keyInput.type = "password";
                }
            }
        </script>

        <?php if (isset($args['description'])) { ?>
        <p class="description">
            <?php esc_html_e($args['description'], 'dpdconnect'); ?>
        </p>
    <?php } ?>
        <?php
    }

    public static function renderUseDpdInput($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option('dpdconnect_parcelshop');

        // output the field
        ?>
        <input type="<?php echo esc_attr($args['type']); ?>"
               id="<?php echo esc_attr($args['label_for']); ?>"
               data-custom="<?php echo esc_attr($args['dpdconnect_custom_data']); ?>"
               name="dpdconnect_parcelshop[<?php echo esc_attr($args['label_for']); ?>]"
               onclick="hideKeySetting()"
                <?php checked( isset( $options['dpdconnect_use_dpd_google_maps_api_key'] ), true ) ?>
        />
        <script>
            function hideKeySetting() {
                var checkbox = document.getElementById("dpdconnect_use_dpd_google_maps_api_key");
                var keySetting = document.getElementsByClassName('google-maps-api-key')[0];

                if (checkbox.checked) {
                    keySetting.style.display = 'none';
                } else {
                    keySetting.style.display = 'table-row';
                }
            }
        </script>

        <?php if (isset($args['description'])) { ?>
        <p class="description">
            <?php esc_html_e($args['description'], 'dpdconnect'); ?>
        </p>
    <?php } ?>
    <?php
    }

    public static function renderAdditionalMethodsInput($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option('dpdconnect_parcelshop');
        $selectedMethods = isset($options[$args['label_for']]) ? explode(',', $options[$args['label_for']]) : [];
        $selectedMethods = array_map('trim', $selectedMethods);

        // Get all available shipping methods from all zones
        $availableShippingMethods = self::getAllShippingMethods();

        ?>
        <div style="max-width: 700px;">
            <?php if (!empty($availableShippingMethods)): ?>
                <p class="description" style="margin-bottom: 10px;">
                    <?php echo __('Select which shipping methods should show the parcelshop selector:', 'dpdconnect'); ?>
                </p>
                <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                    <?php foreach ($availableShippingMethods as $methodData): ?>
                        <?php
                            $isChecked = in_array($methodData['id'], $selectedMethods);
                            $checkboxId = 'parcelshop_method_' . md5($methodData['id']);
                        ?>
                        <label style="display: block; padding: 5px 0; cursor: pointer;">
                            <input
                                type="checkbox"
                                id="<?php echo esc_attr($checkboxId); ?>"
                                class="parcelshop-method-checkbox"
                                value="<?php echo esc_attr($methodData['id']); ?>"
                                <?php checked($isChecked, true); ?>
                            />
                            <strong><?php echo esc_html($methodData['title']); ?></strong>
                            <br>
                            <span style="margin-left: 24px; color: #666; font-size: 11px; font-family: monospace;">
                                ID: <?php echo esc_html($methodData['id']); ?>
                                <?php if (!empty($methodData['zone'])): ?>
                                    | Zone: <?php echo esc_html($methodData['zone']); ?>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Hidden input that stores the comma-separated values -->
                <input type="hidden"
                       id="<?php echo esc_attr($args['label_for']); ?>"
                       name="dpdconnect_parcelshop[<?php echo esc_attr($args['label_for']); ?>]"
                       value="<?php echo esc_attr(implode(',', $selectedMethods)); ?>"
                />

                <script>
                    jQuery(document).ready(function($) {
                        $('.parcelshop-method-checkbox').on('change', function() {
                            var selectedIds = [];
                            $('.parcelshop-method-checkbox:checked').each(function() {
                                selectedIds.push($(this).val());
                            });
                            $('#<?php echo esc_js($args['label_for']); ?>').val(selectedIds.join(','));
                        });
                    });
                </script>
            <?php else: ?>
                <p class="description" style="color: #d63638;">
                    <?php echo __('No shipping methods found. Please configure shipping zones and methods first.', 'dpdconnect'); ?>
                </p>
                <input type="hidden"
                       id="<?php echo esc_attr($args['label_for']); ?>"
                       name="dpdconnect_parcelshop[<?php echo esc_attr($args['label_for']); ?>]"
                       value=""
                />
            <?php endif; ?>

            <?php if (isset($args['description'])): ?>
                <p class="description" style="margin-top: 10px;">
                    <?php esc_html_e($args['description'], 'dpdconnect'); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get all shipping methods from all zones
     * @return array Array of shipping methods with their IDs and titles
     */
    private static function getAllShippingMethods()
    {
        $methods = [];

        // Get all shipping zones
        $zones = \WC_Shipping_Zones::get_zones();

        foreach ($zones as $zone) {
            $zone_obj = new \WC_Shipping_Zone($zone['id']);
            $shipping_methods = $zone_obj->get_shipping_methods();

            foreach ($shipping_methods as $instance_id => $shipping_method) {
                // Skip DPD shipping methods (they're automatically detected)
                if ($shipping_method->id === 'dpd_shipping_method') {
                    continue;
                }

                // Create the full method ID (method_id:instance_id)
                $method_id = $shipping_method->id . ':' . $instance_id;

                $methods[] = [
                    'id' => $method_id,
                    'title' => $shipping_method->get_title() . ' (' . $shipping_method->get_method_title() . ')',
                    'zone' => $zone['zone_name'],
                    'method_id' => $shipping_method->id,
                    'instance_id' => $instance_id,
                ];
            }
        }

        // Also get methods from the "Rest of the World" zone (zone_id = 0)
        $zone_0 = new \WC_Shipping_Zone(0);
        $shipping_methods = $zone_0->get_shipping_methods();

        foreach ($shipping_methods as $instance_id => $shipping_method) {
            if ($shipping_method->id === 'dpd_shipping_method') {
                continue;
            }

            $method_id = $shipping_method->id . ':' . $instance_id;

            $methods[] = [
                'id' => $method_id,
                'title' => $shipping_method->get_title() . ' (' . $shipping_method->get_method_title() . ')',
                'zone' => __('Locations not covered by your other zones', 'dpdconnect'),
                'method_id' => $shipping_method->id,
                'instance_id' => $instance_id,
            ];
        }

        return $methods;
    }

    public static function sectionCallback($args)
    {
        echo __('Google Maps API keys can be obtained directly from Google.', 'dpdconnect');
    }
}
