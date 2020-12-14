<?php

namespace DpdConnect\classes\Settings;

class Company
{
    const PAGE = 'dpdconnect_company_info';
    const SECTION = 'dpdconnect_company_info';

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
            __('Company information', 'dpdconnect'),
            $sectionCallback,
            self::PAGE
        );

        add_settings_field(
            'dpdconnect_company_name', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Company name', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_company_name',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );

        add_settings_field(
            'dpdconnect_company_address', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Company address', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_company_address',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );

        add_settings_field(
            'dpdconnect_company_postal_code', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Company postal code', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_company_postal_code',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );

        add_settings_field(
            'dpdconnect_company_city', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Company city', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_company_city',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );

        add_settings_field(
            'dpdconnect_company_country_code', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Company country Code', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_company_country_code',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
                'description' => 'Please set ISO 2 country code',
            ]
        );

        add_settings_field(
            'dpdconnect_company_phone', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Company phone number', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_company_phone',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );

        add_settings_field(
            'dpdconnect_company_email', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Company email', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_company_email',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );

        add_settings_field(
            'dpdconnect_vat_number', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Vat number', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_vat_number',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );

        add_settings_field(
            'dpdconnect_eori_number', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Eori number', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_eori_number',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );

        add_settings_field(
            'dpdconnect_spr', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('HMRC number', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_spr',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );
    }

    public static function renderDefaultInput($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option('dpdconnect_company_info');
        // output the field
        ?>
        <input type="text"
               id="<?php echo esc_attr($args['label_for']); ?>"
               data-custom="<?php echo esc_attr($args['dpdconnect_custom_data']); ?>"
               name="dpdconnect_company_info[<?php echo esc_attr($args['label_for']); ?>]"
               value="<?php echo $options[$args['label_for']] ?>"
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
        echo __('Company information will be used as sender on label requests.', 'dpdconnect');
    }
}
