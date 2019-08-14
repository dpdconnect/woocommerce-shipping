<?php

namespace DpdConnect\classes;

class Version
{
    const SHOP = 'WooCommerce';

    public static function type()
    {
        return self::SHOP;
    }

    public static function webshop()
    {
        if (!function_exists('get_plugins')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        $plugin_folder = get_plugins('/' . 'woocommerce');
        $plugin_file = 'woocommerce.php';

        if (isset($plugin_folder[$plugin_file]['Version'])) {
            return $plugin_folder[$plugin_file]['Version'];
        } else {
            return null;
        }
    }

    public static function plugin()
    {
        if (!function_exists('get_plugins')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        $plugin_folder = get_plugins('/' . 'dpdconnect');
        $plugin_file = 'dpdconnect.php';

        if (isset($plugin_folder[$plugin_file]['Version'])) {
            return $plugin_folder[$plugin_file]['Version'];
        } else {
            return null;
        }
    }
}
