<?php
/**
 * Settings for flat rate shipping.
 *
 * @package WooCommerce/Classes/Shipping
 */

defined('ABSPATH') || exit;

$cost_desc = __('Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.', 'dpdconnect') . '<br/><br/>' . __('Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent="10" min_fee="20" max_fee=""]</code> for percentage based fees.', 'dpdconnect');

$settings = [
    'zone_title'      => [
        'title'       => __('Method title', 'dpdconnect'),
        'type'        => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'dpdconnect'),
        'default'     => __('DPD Pickup', 'dpdconnect'),
        'desc_tip'    => true,
    ],
    'tax_status' => [
        'title'   => __('Tax status', 'dpdconnect'),
        'type'    => 'select',
        'class'   => 'wc-enhanced-select',
        'default' => 'taxable',
        'options' => [
            'taxable' => __('Taxable', 'dpdconnect'),
            'none'    => _x('None', 'Tax status', 'dpdconnect'),
        ],
    ],
    'cost'       => [
        'title'             => __('Cost', 'dpdconnect'),
        'type'              => 'text',
        'placeholder'       => '',
        'description'       => $cost_desc,
        'default'           => '0',
        'desc_tip'          => true,
        'sanitize_callback' => [ $this, 'sanitize_cost' ],
    ],
];

$shipping_classes = WC()->shipping->get_shipping_classes();

if (! empty($shipping_classes)) {
    $settings['class_costs'] = [
        'title'       => __('Shipping class costs', 'dpdconnect'),
        'type'        => 'title',
        'default'     => '',
        /* translators: %s: URL for link. */
        'description' => sprintf(__('These costs can optionally be added based on the <a href="%s">product shipping class</a>.', 'dpdconnect'), admin_url('admin.php?page=wc-settings&tab=shipping&section=classes')),
    ];
    foreach ($shipping_classes as $shipping_class) {
        if (! isset($shipping_class->term_id)) {
            continue;
        }
        $settings[ 'class_cost_' . $shipping_class->term_id ] = [
            /* translators: %s: shipping class name */
            'title'             => sprintf(__('"%s" shipping class cost', 'dpdconnect'), esc_html($shipping_class->name)),
            'type'              => 'text',
            'placeholder'       => __('N/A', 'dpdconnect'),
            'description'       => $cost_desc,
            'default'           => $this->get_option('class_cost_' . $shipping_class->slug), // Before 2.5.0, we used slug here which caused issues with long setting names.
            'desc_tip'          => true,
            'sanitize_callback' => [ $this, 'sanitize_cost' ],
        ];
    }

    $settings['no_class_cost'] = [
        'title'             => __('No shipping class cost', 'dpdconnect'),
        'type'              => 'text',
        'placeholder'       => __('N/A', 'dpdconnect'),
        'description'       => $cost_desc,
        'default'           => '',
        'desc_tip'          => true,
        'sanitize_callback' => [ $this, 'sanitize_cost' ],
    ];

    $settings['type'] = [
        'title'   => __('Calculation type', 'dpdconnect'),
        'type'    => 'select',
        'class'   => 'wc-enhanced-select',
        'default' => 'class',
        'options' => [
            'class' => __('Per class: Charge shipping for each shipping class individually', 'dpdconnect'),
            'order' => __('Per order: Charge shipping for the most expensive shipping class', 'dpdconnect'),
        ],
    ];
}

return $settings;
