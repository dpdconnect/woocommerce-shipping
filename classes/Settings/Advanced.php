<?php

namespace DpdConnect\classes\Settings;

use DpdConnect\classes\Option;
use DpdConnect\classes\Handlers\Callback;
use DpdConnect\Sdk\Client;

class Advanced
{
    const PAGE = 'dpdconnect_advanced';
    const SECTION = 'dpdconnect_advanced';

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
            __('DPD Advanced settings', 'dpdconnect'),
            $sectionCallback,
            self::PAGE
        );

        add_settings_field(
            'dpdconnect_connect_url', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Connect URL', 'dpdconnect'),
            $callback,
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_connect_url',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
                'type' => 'text',
                'title' => __('The url to DPD Connect', 'dpdconnect'),
                'description' => __(sprintf('Defaults to "%s"', Client::ENDPOINT)),
            ]
        );

        add_settings_field(
            'dpdconnect_connect_callback_url', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Callback URL', 'dpdconnect'),
            [self::class, 'renderInputCallbackUrl'],
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_callback_url',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
                'type' => 'text',
                'title' => __('Responses for async requests will be sent to this base url', 'dpdconnect'),
            ]
        );

        add_settings_field(
            'dpdconnect_async_treshold', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __('Async treshold', 'dpdconnect'),
            [self::class, 'renderInputAsyncTreshold'],
            self::PAGE,
            self::SECTION,
            [
                'label_for' => 'dpdconnect_async_treshold',
                'class' => 'dpdconnect_row',
                'dpdconnect_custom_data' => 'custom',
                'type' => 'number',
                'title' => sprintf(__('Labels requested in bulk will be handled immediately if the amount is below this treshold. The maximum treshold is %s.', 'dpdconnect'), Option::MAX_ASYNC_TRESHOLD),
            ]
        );
    }

    public static function renderDefaultInput($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option('dpdconnect_advanced');

        // output the field
        ?>
        <input type="<?php echo esc_attr($args['type']); ?>"
               id="<?php echo esc_attr($args['label_for']); ?>"
               data-custom="<?php echo esc_attr($args['dpdconnect_custom_data']); ?>"
               name="dpdconnect_advanced[<?php echo esc_attr($args['label_for']); ?>]"
               title="<?php echo esc_attr($args['title']) ?>"
               value="<?php echo $options[$args['label_for']] ?? ''; ?>"
        />

        <?php if (isset($args['description'])) { ?>
        <p class="description">
            <?php esc_html_e($args['description'], 'dpdconnect'); ?>
        </p>
        <?php } ?>
        <?php
    }

    public static function renderInputCallbackUrl($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option('dpdconnect_advanced');

        // output the field
        ?>
        <input type="<?php echo esc_attr($args['type']); ?>"
               id="<?php echo esc_attr($args['label_for']); ?>"
               data-custom="<?php echo esc_attr($args['dpdconnect_custom_data']); ?>"
               name="dpdconnect_advanced[<?php echo esc_attr($args['label_for']); ?>]"
               placeholder=<?php echo Callback::createUrl(); ?>
               title="<?php echo esc_attr($args['title']) ?>"
               value="<?php echo $options[$args['label_for']] ?? ''; ?>"
        />

        <?php if (isset($args['description'])) { ?>
        <p class="description">
            <?php esc_html_e($args['description'], 'dpdconnect'); ?>
        </p>
        <?php } ?>
        <?php
    }

    public static function renderInputAsyncTreshold($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option('dpdconnect_advanced');

        // output the field
        ?>
        <input type="<?php echo esc_attr($args['type']); ?>"
               id="<?php echo esc_attr($args['label_for']); ?>"
               data-custom="<?php echo esc_attr($args['dpdconnect_custom_data']); ?>"
               name="dpdconnect_advanced[<?php echo esc_attr($args['label_for']); ?>]"
               placeholder=<?php echo Option::MAX_ASYNC_TRESHOLD; ?>
               max=<?php echo Option::MAX_ASYNC_TRESHOLD; ?>
               title="<?php echo esc_attr($args['title']) ?>"
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
        echo __('These settings are for debugging purposes. You may leave these settings empty.', 'dpdconnect');
    }
}
