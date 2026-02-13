<?php

namespace DpdConnect\classes\Settings;

use DpdConnect\classes\Connect\Connection;
use DpdConnect\classes\Connect\Product;
use DpdConnect\classes\Service\SettingsDataValidator;
use DpdConnect\Sdk\Exceptions\AuthenticateException;
use DpdConnect\Sdk\Exceptions\HttpException;
use DpdConnect\Sdk\Exceptions\ServerException;

class Credentials
{
    const PAGE = 'dpdconnect_credentials';
    const SECTION = 'dpdconnect_user_credentials';

    public static function handle()
    {
        add_action('admin_init', [self::class, 'render']);
        add_action('wp_ajax_dpdconnect_check_credentials', [self::class, 'ajaxCheckCredentials']);
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

    /**
     * @return void
     */
    public static function ajaxCheckCredentials(): void
    {
        check_ajax_referer('dpdconnect_check_credentials', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'dpdconnect')]);
        }

        $results = [];

        // Check credentials / authentication
        try {
            $jwtToken = Connection::getPublicJwtToken();
            if ($jwtToken) {
                $results['credentials'] = [
                    'success' => true,
                    'message' => __('Credentials are valid. Authentication successful.', 'dpdconnect'),
                ];
            } else {
                $results['credentials'] = [
                    'success' => false,
                    'message' => __('Authentication failed: No JWT Token received.', 'dpdconnect'),
                ];
            }
        } catch (AuthenticateException|HttpException|ServerException $e) {
            $results['credentials'] = [
                'success' => false,
                'message' => __('Authentication failed: ', 'dpdconnect') . $e->getMessage(),
            ];
        }

        // Check available products endpoint
        if ($results['credentials']['success']) {
            try {
                $productConnection = new Product();
                $products = $productConnection->getList();

                if (!empty($products)) {
                    $productNames = array_map(function ($product) {
                        return $product['name'] ?? $product['code'] ?? __('Unknown', 'dpdconnect');
                    }, $products);

                    $results['products'] = [
                        'success' => true,
                        'message' => sprintf(
                            __('Products endpoint reachable. %d product(s) available: %s', 'dpdconnect'),
                            count($products),
                            implode(', ', $productNames)
                        ),
                    ];
                } else {
                    $results['products'] = [
                        'success' => false,
                        'message' => __('Products endpoint reachable but no products returned.', 'dpdconnect'),
                    ];
                }
            } catch (\Exception $e) {
                $results['products'] = [
                    'success' => false,
                    'message' => __('Failed to reach products endpoint: ', 'dpdconnect') . $e->getMessage(),
                ];
            }
        } else {
            $results['products'] = [
                'success' => false,
                'message' => __('Skipped: Credentials must be valid before checking products.', 'dpdconnect'),
            ];
        }

        wp_send_json_success($results);
    }

    /**
     * @param $args
     * @return void
     */
    public static function sectionCallback($args): void
    {
        $errors = SettingsDataValidator::validateCredentialSettings();
        SettingsDataValidator::printValidationErrors($errors);

        $nonce = wp_create_nonce('dpdconnect_check_credentials');
        ?>
        <div style="margin-top: 10px;">
            <button type="button" id="dpdconnect-check-credentials" class="button button-secondary">
                <?php echo __('Check Credentials', 'dpdconnect'); ?>
            </button>
            <span id="dpdconnect-check-spinner" class="spinner" style="float: none; margin-top: 0;"></span>
        </div>
        <div id="dpdconnect-check-results" style="margin-top: 10px;"></div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#dpdconnect-check-credentials').on('click', function() {
                    var $button = $(this);
                    var $spinner = $('#dpdconnect-check-spinner');
                    var $results = $('#dpdconnect-check-results');

                    $button.prop('disabled', true);
                    $spinner.addClass('is-active');
                    $results.html('');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'dpdconnect_check_credentials',
                            nonce: '<?php echo esc_js($nonce); ?>'
                        },
                        success: function(response) {
                            $spinner.removeClass('is-active');
                            $button.prop('disabled', false);

                            if (response.success && response.data) {
                                var html = '<div style="padding: 10px; border: 1px solid #ccc; background: #f9f9f9; border-radius: 4px;">';

                                // Credentials result
                                var cred = response.data.credentials;
                                var credColor = cred.success ? '#46b450' : '#dc3232';
                                var credIcon = cred.success ? '&#10004;' : '&#10008;';
                                html += '<p style="margin: 5px 0; color: ' + credColor + ';"><strong>' + credIcon + ' <?php echo esc_js(__('Credentials:', 'dpdconnect')); ?></strong> ' + cred.message + '</p>';

                                // Products result
                                var prod = response.data.products;
                                var prodColor = prod.success ? '#46b450' : '#dc3232';
                                var prodIcon = prod.success ? '&#10004;' : '&#10008;';
                                html += '<p style="margin: 5px 0; color: ' + prodColor + ';"><strong>' + prodIcon + ' <?php echo esc_js(__('Products:', 'dpdconnect')); ?></strong> ' + prod.message + '</p>';

                                html += '</div>';
                                $results.html(html);
                            } else {
                                $results.html('<p style="color: #dc3232;"><?php echo esc_js(__('An unexpected error occurred.', 'dpdconnect')); ?></p>');
                            }
                        },
                        error: function() {
                            $spinner.removeClass('is-active');
                            $button.prop('disabled', false);
                            $results.html('<p style="color: #dc3232;"><?php echo esc_js(__('Connection error. Please try again.', 'dpdconnect')); ?></p>');
                        }
                    });
                });
            });
        </script>
        <?php
    }
}
