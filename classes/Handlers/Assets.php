<?php

namespace DpdConnect\classes\Handlers;

class Assets
{
    public static function handle()
    {
        add_action('admin_enqueue_scripts', function () {
            $path = plugin_dir_path(__FILE__) . '../../assets/css/dpdconnect.css';
            wp_register_style('dpdconnect_wp_admin_css', plugins_url('../../assets/css/dpdconnect.css', __FILE__), false, hash_file('crc32b', $path));
            wp_enqueue_style('dpdconnect_wp_admin_css');
        });

        add_action('wp_enqueue_scripts', function () {
            $path = plugin_dir_path(__FILE__) . '../../assets/css/dpd_checkout.css';
            wp_register_style('dpdconnect_checkout_css', plugins_url('../../assets/css/dpd_checkout.css', __FILE__), false, hash_file('crc32b', $path));
            wp_enqueue_style('dpdconnect_checkout_css');
        });
    }
}
