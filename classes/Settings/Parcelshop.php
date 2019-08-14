<?php

namespace DpdConnect\classes\Settings;

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
        $callback = [self::class, 'renderDefaultInput'];

        register_setting(self::PAGE, self::SECTION);

        add_settings_section(
            self::SECTION,
            __('Parcelshop settings', 'dpdconnect'),
            $sectionCallback,
            self::PAGE
        );

        add_settings_field(
            'dpdconnect_google_maps_api_client_key', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Google Maps Static & Javascript API key', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_google_maps_api_client_key',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
                'type' => 'password',
                'description' => __('This key is sent to your visitors browser and is used to render Google Maps in the checkout so visitors can select a parcelshop.', 'dpdconnect'),
            ]
        );

        add_settings_field(
            'dpdconnect_google_maps_api_server_key', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Google Maps Geocoding API key', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_google_maps_api_server_key',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
                'type' => 'password',
                'description' => __('This key is used for requesting Geographical coordinates based on zipcodes. This key remains on the server.', 'dpdconnect'),
            ]
        );
    }

    public static function renderDefaultInput($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option('dpdconnect_parcelshop');
        // output the field
        ?>
        <input type="<?php echo esc_attr($args['type']); ?>"
               id="<?php echo esc_attr($args['label_for']); ?>"
               data-custom="<?php echo esc_attr($args['dpdconnect_custom_data']); ?>"
               name="dpdconnect_parcelshop[<?php echo esc_attr($args['label_for']); ?>]"
               value="<?php echo $options[ $args['label_for']] ?>"
        />

        <?php if (isset($args['description'])) { ?>
        <p class="description">
            <?php esc_html_e($args['description'], 'dpdconnect'); ?>
        </p>
        <?php } ?>
        <?php
    }

    public static function sectionCallback($args)
    {
        echo __('Google Maps API keys can be obtained directly from Google. You can use the same key for both fields, which however will result in reaching Googles rate limit more quickly.', 'dpdconnect');
    }
}
