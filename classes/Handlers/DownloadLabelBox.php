<?php

namespace DpdConnect\classes\Handlers;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use DpdConnect\classes\Database\Label;
use DpdConnect\classes\enums\ParcelType;

class DownloadLabelBox
{
    public static function handle()
    {
        add_filter('add_meta_boxes', [self::class, 'add']);
    }

    public static function add()
    {
        $screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        add_meta_box('dpdconnect_pdf', __('DPD Connect Download Labels', 'dpdconnect'), [self::class, 'render'], $screen, 'side', 'high');
    }

    /**
     * @param \WC_Order $order
     * @return void
     */
    public static function render($order)
    {
        $labelRepo = new Label();

        if ($order instanceof \WP_Post) {
            $order = wc_get_order($order->ID);
        }

        $shippingLabels = $labelRepo->getByOrderId($order->get_id(), ParcelType::TYPEREGULAR, true);
        $returnLabels = $labelRepo->getByOrderId($order->get_id(), ParcelType::TYPERETURN, true);

        echo '<table>';

        if (!$shippingLabels && !$returnLabels) {
            echo '<tr><td>' . __('No labels available.') . '</a></td></tr>';
        }

        if ($shippingLabels) {
            foreach ($shippingLabels as $shippingLabel) {
                $shippingId = $shippingLabel['id'];
                $shippingUrl = add_query_arg(['plugin' => 'dpdconnect', 'file' => 'shipping_label', 'id' => $shippingId], admin_url());
                echo '<tr><td><a href="' . $shippingUrl . '">' . __('Shipping label', 'dpdconnect') . '</a></td>';
                echo '<td>' . $shippingLabel['created_at'] . '</td></tr>';
            }
        }

        if ($returnLabels) {
            foreach ($returnLabels as $returnLabel) {
                $returnId = $returnLabel['id'];
                $returnUrl = add_query_arg(['plugin' => 'dpdconnect', 'file' => 'shipping_label', 'id' => $returnId], admin_url());
                echo '<tr><td><a href="' . $returnUrl . '">' . __('Return label', 'dpdconnect') . '</a></td>';
                echo '<td>' . $returnLabel['created_at'] . '</td></tr>';
            }
        }

        echo '</table>';
    }
}
