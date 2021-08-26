<?php

namespace DpdConnect\classes\Handlers;

class Update
{
    public static function handle()
    {
        register_activation_hook(dirname(__DIR__) . '/dpdconnect.php', 'dpdconnect_activate');
        self::updateOptions();
    }

    public static function updateOptions()
    {
        // Remove old api keys, store them in new api key if they were set
        if (DPDCONNECT_PLUGIN_VERSION != '1.2.9') {
            $options = get_option('dpdconnect_parcelshop');

            if (!isset($options['dpdconnect_google_maps_api_key'])) {
                $apiKey = '';
                if (empty($apiKey) && !empty($options['dpdconnect_google_maps_api_client_key'])) {
                    $apiKey = $options['dpdconnect_google_maps_api_client_key'];
                }
                if (empty($apiKey) && !empty($options['dpdconnect_google_maps_api_server_key'])) {
                    $apiKey = $options['dpdconnect_google_maps_api_server_key'];
                }

                $options['dpdconnect_google_maps_api_key'] = $apiKey;
                $options['dpdconnect_use_dpd_google_maps_api_key'] = 'on';

                // Unset old options
                unset($options['dpdconnect_google_maps_api_client_key']);
                unset($options['dpdconnect_google_maps_api_server_key']);
                update_option('dpdconnect_parcelshop', $options);
            }

        }
    }
}
