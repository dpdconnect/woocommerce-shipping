<?php

namespace DpdConnect\classes\Handlers;

class Update
{
    public static function handle()
    {
        register_activation_hook(dirname(__DIR__) . '/dpdconnect.php', 'dpdconnect_activate');
        self::updateOptions();
        self::updateDatabaseIndexes();
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

    /**
     * Add composite indexes for better query performance on existing installations
     * These indexes optimize queries that filter by order_id and type, and sort by created_at
     */
    public static function updateDatabaseIndexes()
    {
        global $wpdb;

        // Check if migration has already been run
        $db_version = get_option('dpdconnect_db_version', '1.0');

        if (version_compare($db_version, '2.0.1', '<')) {
            // Add composite index to dpdconnect_labels table
            $labels_table = $wpdb->prefix . 'dpdconnect_labels';
            $index_exists = $wpdb->get_results(
                "SHOW INDEX FROM $labels_table WHERE Key_name = 'order_id_type_created_at'"
            );

            if (empty($index_exists)) {
                $wpdb->query(
                    "ALTER TABLE $labels_table ADD INDEX order_id_type_created_at (order_id, type, created_at)"
                );
            }

            // Add composite index to dpdconnect_jobs table
            $jobs_table = $wpdb->prefix . 'dpdconnect_jobs';
            $index_exists = $wpdb->get_results(
                "SHOW INDEX FROM $jobs_table WHERE Key_name = 'order_id_type_created_at'"
            );

            if (empty($index_exists)) {
                $wpdb->query(
                    "ALTER TABLE $jobs_table ADD INDEX order_id_type_created_at (order_id, type, created_at)"
                );
            }

            // Update database version to mark migration as complete
            update_option('dpdconnect_db_version', '2.0.1');
        }
    }
}
