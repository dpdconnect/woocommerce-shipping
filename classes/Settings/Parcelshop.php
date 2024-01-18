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

    public static function sectionCallback($args)
    {
        echo __('Google Maps API keys can be obtained directly from Google.', 'dpdconnect');
    }
}
