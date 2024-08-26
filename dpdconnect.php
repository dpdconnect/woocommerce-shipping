<?php

require_once('vendor/autoload.php');

/**
 * Plugin Name: DPD Connect for WooCommerce
 * Plugin URI: https://integrations.dpd.nl/
 * Description: Enables the posibility to integrate DPD Parcel Shop Finder service into your e-commerce store with a breeze.
 * Version: 1.4.7
 * Author: DPD / X-Interactive.nl
 * Author URI: https://github.com/dpdconnect
 * License: GPL
 * Text Domain: dpdconnect
 * Domain Path: /languages
 */

use DpdConnect\classes\Handlers\DpdAttributes;
use DpdConnect\classes\Handlers\Update;
use DpdConnect\classes\Router;
use DpdConnect\classes\Handlers\Assets;
use DpdConnect\classes\Handlers\Notice;
use DpdConnect\classes\Handlers\Pickup;
use DpdConnect\classes\Settings\Handler;
use DpdConnect\classes\Handlers\Activate;
use DpdConnect\classes\Handlers\Callback;
use DpdConnect\classes\Handlers\OrderColumn;
use DpdConnect\classes\Handlers\Translation;
use DpdConnect\classes\Handlers\LabelRequest;
use DpdConnect\classes\Handlers\DownloadLabelBox;
use DpdConnect\classes\Handlers\OrderListActions;
use DpdConnect\classes\Handlers\ShippingMethods;
use DpdConnect\classes\Handlers\GenerateLabelBox;
use DpdConnect\classes\Handlers\ShippingAttributes;

// Prevent direct file access
defined('ABSPATH') or exit;

// SET Root path
define('DPDCONNECT_PLUGIN_ROOT_PATH', plugin_dir_path(__FILE__));

// SET plugin version
require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
define('DPDCONNECT_PLUGIN_VERSION', get_plugin_data(DPDCONNECT_PLUGIN_ROOT_PATH . '/dpdconnect.php')['Version']);

// Add tables for storing labels
Activate::handle();

// Execute updates when needed
Update::handle();

// Load available translations
add_action('plugins_loaded', [Translation::class, 'handle']);

// Make plugin HPOS compatible
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

/**
 * Check if WooCommerce is active
 */
if (is_plugin_active('woocommerce/woocommerce.php')) {

    /**
     * Add settings admin menu
     */
    Handler::handle();

    /**
     * Load assets
     */
    Assets::handle();

    /**
     * Add Admin ShopOrder metaBoxes
     */
    GenerateLabelBox::handle();
    DownloadLabelBox::handle();

    /**
     * Add DPD Pickup functionality
     */
    Pickup::handle();

    /**
     * Add Admin Product attributes for Customs
     */
    ShippingAttributes::handle();

    /**
     * Add shipping methods and classes
     */
    ShippingMethods::handle();

    /**
     * Add functions for notifications
     */
    Notice::handle();

    /**
     * Add DPD Order Bulk Actions functionality
     */
    LabelRequest::handle();

    /**
     * Listen for incoming callbacks
     */
    Callback::handle();

    /**
     * Add column to WooCommerce order table
     */
    OrderColumn::handle();

    /**
     * Initiate router
     */
    Router::init($_GET);

    /**
     * Add bulk actions to woocommerce order table
     */
    OrderListActions::handle();
}
