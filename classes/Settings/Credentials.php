<?php

namespace DpdConnect\classes\Settings;

class Credentials
{
    const PAGE = 'dpdconnect_credentials';
    const SECTION = 'dpdconnect_user_credentials';

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
            __('DPD Connect Credentials', 'dpdconnect'),
            $sectionCallback,
            self::PAGE
        );

        add_settings_field(
            'dpdconnect_connect_username', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Connect Username', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_connect_username',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
                'type' => 'text',
            ]
        );

        add_settings_field(
            'dpdconnect_connect_password', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Connect Password', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_connect_password',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
                'type' => 'password',
            ]
        );
    }

    public static function renderDefaultInput($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option('dpdconnect_user_credentials');

        // output the field
        ?>
        <input type="<?php echo esc_attr($args['type']); ?>"
               id="<?php echo esc_attr($args['label_for']); ?>"
               data-custom="<?php echo esc_attr($args['dpdconnect_custom_data']); ?>"
               name="dpdconnect_user_credentials[<?php echo esc_attr($args['label_for']); ?>]"
               value="<?php echo $options[$args['label_for']] ?? '' ?>"
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
        // Todo: Credentials check could be done here
    }
}
