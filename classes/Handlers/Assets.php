<?php

namespace DpdConnect\classes\Handlers;

class Assets
{
    public static function handle()
    {
        add_action('admin_enqueue_scripts', function () {
            wp_register_style('dpdconnect_wp_admin_css', plugins_url('../../assets/css/dpdconnect.css', __FILE__), false, '1.0.0');
            wp_enqueue_style('dpdconnect_wp_admin_css');
        });

        add_action('wp_enqueue_scripts', function () {
            wp_register_style('dpdconnect_checkout_css', plugins_url('../../assets/css/dpd_checkout.css', __FILE__), false, '1.0.0');
            wp_enqueue_style('dpdconnect_checkout_css');
        });
    }
}
