<?php

namespace DpdConnect\classes\shippingmethods;

// Prevent direct file access
defined('ABSPATH') or exit;

use DpdConnect\classes\Connect\Product;
use DpdConnect\classes\TypeHelper;
use WC_Eval_Math;
use DateTime;
use DateTimeZone;
use WC_Shipping_Method;

class DPDShippingMethod extends WC_Shipping_Method
{
    /**
     * Cost passed to [fee] shortcode.
     *
     * @var string Cost.
     */
    protected $fee_cost = '';
    /**
     * @var bool
     */
    public $is_dpd_saturday = false;
    /**
     * @var bool
     */
    public $is_dpd_pickup = false;
    /**
     * @var array
     */
    protected $modal_settings = [];

    /**
     * Constructor.
     *
     * @param int $instance_id Shipping method instance ID.
     */
    public function __construct($instance_id = 0)
    {

        $this->id                 = 'dpd_shipping_method';
        $this->title              = 'DPD Shipping Method';
        $this->method_title       = __($this->title, 'dpdconnect');
        $this->method_description = __('Please select a label type', 'dpdconnect');
        $this->instance_id        = absint($instance_id);
        $this->supports           = [
            'shipping-zones',
//            'settings',
            'instance-settings',
            'instance-settings-modal',
        ];

        if ($instance_id === 0) {
            return;
        }

        $this->init();

        add_action('woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ]);
    }

    private function init_modal_settings()
    {
        $this->modal_settings = get_option('woocommerce_' . $this->id . '_' . $this->instance_id . '_settings');
    }

    /**
     * Init user set variables.
     */
    public function init()
    {
        $this->instance_form_fields = include 'includes/settings-dpd-shipping-method.php';

        $this->init_instance_settings();
        $this->init_settings();
        $this->init_modal_settings();

        $this->method_title         = $this->modal_settings['zone_title'] ?? $this->get_option('title', $this->title);
        $this->tax_status           = $this->modal_settings['tax_status'] ?? $this->get_option('tax_status');
        $this->cost                 = $this->modal_settings['cost'] ?? $this->get_option('cost');
        $this->type                 = $this->modal_settings['dpd_method_type'] ?? $this->get_option('type', 'class');

        $this->init_dpd();
    }

    private function init_dpd()
    {
        $products = new Product();
        $selectedProduct = $products->getProductByCode($this->type);
        if ($selectedProduct !== null) {
            $this->title = $this->modal_settings['zone_title'] ?? $this->get_option('title', $this->title);
            $this->method_description = $selectedProduct['description'];

            $this->is_dpd_saturday = TypeHelper::isSaturday($selectedProduct);
            $this->is_dpd_pickup = TypeHelper::isParcelshop($selectedProduct);
        }

        // Show DPD Saturday settings
        if ($this->is_dpd_saturday) {
            $this->instance_form_fields = array_merge($this->instance_form_fields, $this->init_saturday_settings());
        }
    }

    /**
     * Evaluate a cost from a sum/string.
     *
     * @param  string $sum Sum of shipping.
     * @param  array  $args Args.
     * @return string
     */
    protected function evaluate_cost($sum, $args = [])
    {
        include_once 'includes/class-wc-eval-math.php';

        // Allow 3rd parties to process shipping cost arguments.
        $args           = apply_filters('woocommerce_evaluate_shipping_cost_args', $args, $sum, $this);
        $locale         = localeconv();
        $decimals       = [ wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'], ',' ];
        $this->fee_cost = $args['cost'];

        // Expand shortcodes.
        add_shortcode('fee', [ $this, 'fee' ]);

        $sum = do_shortcode(
            str_replace(
                [
                    '[qty]',
                    '[cost]',
                ],
                [
                    $args['qty'],
                    $args['cost'],
                ],
                $sum
            )
        );

        remove_shortcode('fee', [ $this, 'fee' ]);

        // Remove whitespace from string.
        $sum = preg_replace('/\s+/', '', $sum);

        // Remove locale from string.
        $sum = str_replace($decimals, '.', $sum);

        // Trim invalid start/end characters.
        $sum = rtrim(ltrim($sum, "\t\n\r\0\x0B+*/"), "\t\n\r\0\x0B+-*/");

        // Do the math.
        return $sum ? WC_Eval_Math::evaluate($sum) : 0;
    }

    /**
     * Work out fee (shortcode).
     *
     * @param  array $atts Attributes.
     * @return string
     */
    public function fee($atts)
    {
        $atts = shortcode_atts(
            [
                'percent' => '',
                'min_fee' => '',
                'max_fee' => '',
            ],
            $atts,
            'fee'
        );

        $calculated_fee = 0;

        if ($atts['percent']) {
            $calculated_fee = $this->fee_cost * ( floatval($atts['percent']) / 100 );
        }

        if ($atts['min_fee'] && $calculated_fee < $atts['min_fee']) {
            $calculated_fee = $atts['min_fee'];
        }

        if ($atts['max_fee'] && $calculated_fee > $atts['max_fee']) {
            $calculated_fee = $atts['max_fee'];
        }

        return $calculated_fee;
    }

    /**
     * Calculate the shipping costs.
     *
     * @param array $package Package of items from cart.
     */
    public function calculate_shipping($package = [])
    {
        $rate = [
            'id'      => $this->get_rate_id(),
            'label'   => $this->title,
            'cost'    => 0,
            'package' => $package,
        ];

        // Calculate the costs.
        $has_costs = false; // True when a cost is set. False if all costs are blank strings.
        $cost      = $this->get_option('cost');

        if ('' !== $cost) {
            $has_costs    = true;
            $rate['cost'] = $this->evaluate_cost(
                $cost,
                [
                    'qty'  => $this->get_package_item_qty($package),
                    'cost' => $package['contents_cost'],
                ]
            );
        }

        // Add shipping class costs.
        $shipping_classes = WC()->shipping->get_shipping_classes();

        if (! empty($shipping_classes)) {
            $found_shipping_classes = $this->find_shipping_classes($package);
            $highest_class_cost     = 0;

            foreach ($found_shipping_classes as $shipping_class => $products) {
                // Also handles BW compatibility when slugs were used instead of ids.
                $shipping_class_term = get_term_by('slug', $shipping_class, 'product_shipping_class');
                $class_cost_string   = $shipping_class_term && $shipping_class_term->term_id ? $this->get_option('class_cost_' . $shipping_class_term->term_id, $this->get_option('class_cost_' . $shipping_class, '')) : $this->get_option('no_class_cost', '');

                if ('' === $class_cost_string) {
                    continue;
                }

                $has_costs  = true;
                $class_cost = $this->evaluate_cost(
                    $class_cost_string,
                    [
                        'qty'  => array_sum(wp_list_pluck($products, 'quantity')),
                        'cost' => array_sum(wp_list_pluck($products, 'line_total')),
                    ]
                );

                if ('class' === $this->type) {
                    $rate['cost'] += $class_cost;
                } else {
                    $highest_class_cost = $class_cost > $highest_class_cost ? $class_cost : $highest_class_cost;
                }
            }

            if ('order' === $this->type && $highest_class_cost) {
                $rate['cost'] += $highest_class_cost;
            }
        }

        if ($has_costs) {
            $this->add_rate($rate);
        }

        /**
         * Developers can add additional flat rates based on this one via this action since @version 2.4.
         *
         * Previously there were (overly complex) options to add additional rates however this was not user.
         * friendly and goes against what Flat Rate Shipping was originally intended for.
         */
        do_action('woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate);
    }

    /**
     * Get items in package.
     *
     * @param  array $package Package of items from cart.
     * @return int
     */
    public function get_package_item_qty($package)
    {
        $total_quantity = 0;
        foreach ($package['contents'] as $item_id => $values) {
            if ($values['quantity'] > 0 && $values['data']->needs_shipping()) {
                $total_quantity += $values['quantity'];
            }
        }
        return $total_quantity;
    }

    /**
     * Finds and returns shipping classes and the products with said class.
     *
     * @param mixed $package Package of items from cart.
     * @return array
     */
    public function find_shipping_classes($package)
    {
        $found_shipping_classes = [];

        foreach ($package['contents'] as $item_id => $values) {
            if ($values['data']->needs_shipping()) {
                $found_class = $values['data']->get_shipping_class();

                if (! isset($found_shipping_classes[ $found_class ])) {
                    $found_shipping_classes[ $found_class ] = [];
                }

                $found_shipping_classes[ $found_class ][ $item_id ] = $values;
            }
        }

        return $found_shipping_classes;
    }

    /**
     * Sanitize the cost field.
     *
     * @since 3.4.0
     * @param string $value Unsanitized value.
     * @return string
     */
    public function sanitize_cost($value)
    {
        $value = is_null($value) ? '' : $value;
        $value = wp_kses_post(trim(wp_unslash($value)));
        $value = str_replace([ get_woocommerce_currency_symbol(), html_entity_decode(get_woocommerce_currency_symbol()) ], '', $value);
        return $value;
    }

    public function hide($shippingMethods)
    {
        if (! $this->is_dpd_saturday) {
            return $shippingMethods;
        }

        $searchString = $this->id;

        foreach ($shippingMethods as $key => $value) {
            if ($searchString === substr($key, 0, strlen($searchString))) {
                $currentDay = date('w');
                $fromDay = $this->modal_settings['show_from_day'];
                $untillDay = $this->modal_settings['show_untill_day'];
                $currentTime = (new DateTime())->setTimeZone(new DateTimeZone('Europe/Amsterdam'));
                $fromTime = new DateTime($this->modal_settings['show_from_time'], new DateTimeZone('Europe/Amsterdam'));
                $untillTime = new DateTime($this->modal_settings['show_untill_time'], new DateTimeZone('Europe/Amsterdam'));

                if ($currentDay < $fromDay) {
                    unset($shippingMethods[$key]);
                    continue;
                }

                if ($currentDay > $untillDay) {
                    unset($shippingMethods[$key]);
                    continue;
                }

                if ($fromTime->diff($currentTime)->format('%R') === '-') {
                    unset($shippingMethods[$key]);
                    continue;
                }

                if ($untillTime->diff($currentTime)->format('%R') === '+') {
                    unset($shippingMethods[$key]);
                    continue;
                }
            }
        }

        return $shippingMethods;
    }

    private function init_saturday_settings()
    {
        $settings['show_from_day'] = [
            'title' => __('Show from day', 'dpdconnect'),
            'type' => 'select',
            'description' => __('From which day and time in the week Saturday deliveries will be available in checkout', 'dpdconnect'),
            'options' => [
                '1' => __('Monday'),
                '2' => __('Tuesday'),
                '3' => __('Wednesday'),
                '4' => __('Thursday'),
                '5' => __('Friday'),
                '6' => __('Saturday'),
                '7' => __('Sunday'),
            ],
        ];
        $settings['show_from_time'] = [
            'title' => __('Show from time', 'dpdconnect'),
            'type' => 'time',
            'description' => __('From which time Saturday deliveries will be available in checkout', 'dpdconnect'),
        ];
        $settings['show_untill_day'] = [
            'title' => __('Show untill day', 'dpdconnect'),
            'type' => 'select',
            'description' => __('Untill which day in the week Saturday deliveries will be available in checkout', 'dpdconnect'),
            'options' => [
                '1' => __('Monday'),
                '2' => __('Tuesday'),
                '3' => __('Wednesday'),
                '4' => __('Thursday'),
                '5' => __('Friday'),
                '6' => __('Saturday'),
                '7' => __('Sunday'),
            ],
        ];
        $settings['show_untill_time'] = [
            'title' => __('Show untill time', 'dpdconnect'),
            'type' => 'time',
            'description' => __('Untill which time Saturday deliveries will be available in checkout', 'dpdconnect'),
        ];

        return $settings;
    }
}
