<?php

namespace DpdConnect\classes\Handlers;

class Activate
{
    public static function handle()
    {
        register_activation_hook(dirname(__DIR__) . '/dpdconnect.php', 'dpdconnect_activate');
        self::updateDb();
    }

    public static function updateDb()
    {
        global $wpdb;

        $version = get_option('my_plugin_version', '1.1');

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'dpdconnect_labels';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            order_id int(11) NOT NULL,
            contents mediumblob NOT NULL,
            type tinyint NOT NULL,
            shipment_identifier varchar(255) NOT NULL,
            parcel_numbers varchar(255) NOT NULL,
            PRIMARY KEY id (id),
            INDEX created_at (created_at)
        ) $charset_collate;";
        $wpdb->query($sql);

        $table_name = $wpdb->prefix . 'dpdconnect_batches';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            shipment_count smallint(5) NOT NULL,
            success_count smallint(5) DEFAULT 0,
            failure_count smallint(5) DEFAULT 0,
            status varchar(255) NOT NULL,
            PRIMARY KEY id (id),
            INDEX created_at (created_at)
        ) $charset_collate;";
        $wpdb->query($sql);

        $table_name = $wpdb->prefix . 'dpdconnect_jobs';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            external_id varchar(255) NOT NULL,
            batch_id varchar(255) NOT NULL,
            order_id varchar(255) NOT NULL,
            status varchar(255) NOT NULL,
            type varchar(255) NOT NULL,
            error text,
            state_message text,
            label_id int(11) NULL,
            PRIMARY KEY id (id),
            INDEX created_at (created_at),
            INDEX batch_id (batch_id)
        ) $charset_collate;";
        $wpdb->query($sql);
    }
}
