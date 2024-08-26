<?php

namespace DpdConnect\classes\Settings;

class General
{
    const PAGE = 'dpdconnect_general';
    const SECTION = 'dpdconnect_general';

    public static function handle()
    {
        add_action('admin_init', [self::class, 'render']);
    }

    public static function render()
    {
        $sectionCallback = [self::class, 'sectionCallback'];
        $defaultCallback = [self::class, 'renderDefaultInput'];

        register_setting(self::PAGE, self::SECTION);

        add_settings_section(
            self::SECTION,
            __('General settings', 'dpdconnect'),
            $sectionCallback,
            self::PAGE
        );

        add_settings_field(
            'dpdconnect_account_type', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('DPD Account type', 'dpdconnect'),
            [self::class, 'renderInputAccountType'],
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_account_type',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );

        add_settings_field(
            'dpdconnect_depot', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('DPD Depot', 'dpdconnect'),
            $defaultCallback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_depot',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );

        add_settings_field(
            'dpdconnect_label_format', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __(' DPD Label Format', 'dpdconnect'),
            [self::class, 'renderInputLabelFormat'],
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_label_format',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );

        add_settings_field(
            'dpdconnect_send_trackingemail',
            __(' DPD Send Trackingemail', 'dpdconnect'),
            [self::class, 'renderInputSendTrackingemail'],
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_send_trackingemail',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );
        add_settings_field(
            'dpdconnect_download_format',
            __(' DPD Download format', 'dpdconnect'),
            [self::class, 'renderInputDownloadFormat'],
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_download_format',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
            ]
        );
    }

    public static function renderDefaultInput($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option('dpdconnect_general');

        // output the field
        ?>
        <input type="text"
               id="<?php echo esc_attr($args['label_for']); ?>"
               data-custom="<?php echo esc_attr($args['dpdconnect_custom_data']); ?>"
               name="dpdconnect_general[<?php echo esc_attr($args['label_for']); ?>]"
               value="<?php echo $options[$args['label_for']] ?? '' ?>"
        />

        <?php if (isset($args['description'])) { ?>
        <p class="description">
            <?php esc_html_e($args['description'], 'dpdconnect'); ?>
        </p>
        <?php } ?>

        <?php
    }

    public static function renderInputLabelFormat($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option('dpdconnect_general');
        // output the field
        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                data-custom="<?php echo esc_attr($args['dpdconnect_custom_data']); ?>"
                name="dpdconnect_general[<?php echo esc_attr($args['label_for']); ?>]"
        >
            <option value="A4" <?php echo isset($options[ $args['label_for'] ]) ? ( selected($options[ $args['label_for'] ], 'A4', false) ) : ( '' ); ?>>
                <?php esc_html_e('A4 format', 'dpdconnect'); ?>
            </option>
            <option value="A6" <?php echo isset($options[ $args['label_for'] ]) ? ( selected($options[ $args['label_for'] ], 'A6', false) ) : ( '' ); ?>>
                <?php esc_html_e('A6 format', 'dpdconnect'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Choose the correct label format for your store.', 'dpdconnect'); ?>
        </p>
        <?php
    }

    public static function renderInputDownloadFormat($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option('dpdconnect_general');
        // output the field
        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                data-custom="<?php echo esc_attr($args['dpdconnect_custom_data']); ?>"
                name="dpdconnect_general[<?php echo esc_attr($args['label_for']); ?>]"
        >
            <option value="zip" <?php echo isset($options[ $args['label_for'] ]) ? ( selected($options[ $args['label_for'] ], 'zip', false) ) : ( '' ); ?>>
                <?php esc_html_e('Zip file', 'dpdconnect'); ?>
            </option>
            <option value="pdf" <?php echo isset($options[ $args['label_for'] ]) ? ( selected($options[ $args['label_for'] ], 'pdf', false) ) : ( '' ); ?>>
                <?php esc_html_e('Merged PDF file', 'dpdconnect'); ?>
            </option>
        </select>
        <?php
    }

    public static function renderInputAccountType($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option('dpdconnect_general');
        // output the field
        ?>
            <select id="<?php echo esc_attr($args['label_for']); ?>"
                    data-custom="<?php echo esc_attr($args['dpdconnect_custom_data']); ?>"
                    name="dpdconnect_general[<?php echo esc_attr($args['label_for']); ?>]"
            >
                <option value="b2c" <?php echo isset($options[ $args['label_for'] ]) ? ( selected($options[ $args['label_for'] ], 'b2c', false) ) : ( '' ); ?>>
                    <?php esc_html_e('B2C', 'dpdconnect'); ?>
                </option>
                <option value="b2b" <?php echo isset($options[ $args['label_for'] ]) ? ( selected($options[ $args['label_for'] ], 'b2b', false) ) : ( '' ); ?>>
                    <?php esc_html_e('B2B', 'dpdconnect'); ?>
                </option>
            </select>
            <p class="description">
                <?php esc_html_e('Choose the correct account type for your store.', 'dpdconnect'); ?>
            </p>
        <?php
    }

    public static function renderInputSendTrackingemail($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option('dpdconnect_general');
        // output the field
        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                data-custom="<?php echo esc_attr($args['dpdconnect_custom_data']); ?>"
                name="dpdconnect_general[<?php echo esc_attr($args['label_for']); ?>]"
        >
            <option value="disabled" <?php echo isset($options[ $args['label_for'] ]) ? ( selected($options[ $args['label_for'] ], 'disabled', false) ) : ( '' ); ?>>
                <?php esc_html_e('DISABLED', 'dpdconnect'); ?>
            </option>
            <option value="enabled" <?php echo isset($options[ $args['label_for'] ]) ? ( selected($options[ $args['label_for'] ], 'enabled', false) ) : ( '' ); ?>>
                <?php esc_html_e('ENABLED', 'dpdconnect'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Enable or disable sending tracking emails.', 'dpdconnect'); ?>
        </p>
        <?php
    }

    public static function sectionCallback($args)
    {
    }
}
