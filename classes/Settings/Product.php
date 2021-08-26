<?php

namespace DpdConnect\classes\Settings;

class Product
{
    const PAGE = 'dpdconnect_products';
    const SECTION = 'dpdconnect_products';

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
            __('Product attributes', 'dpdconnect'),
            $sectionCallback,
            self::PAGE
        );

        add_settings_field(
            'dpdconnect_default_hs_code', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Default Harmonized System Code', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_default_hs_code',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );

        add_settings_field(
            'dpdconnect_default_origin_country', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Default Country of Origin', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_default_origin_country',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );

        add_settings_field(
            'dpdconnect_default_product_weight', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Default Product Weight (kg)', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_default_product_weight',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );
    }

    public static function renderDefaultInput($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option('dpdconnect_products');

        ?>
        <input type="text"
               id="<?php echo esc_attr($args['label_for']); ?>"
               data-custom="<?php echo esc_attr($args['dpdconnect_custom_data']); ?>"
               name="dpdconnect_products[<?php echo esc_attr($args['label_for']); ?>]"
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
    }
}
