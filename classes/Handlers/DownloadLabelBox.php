<?php

namespace DpdConnect\classes\Handlers;

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
        add_meta_box('dpdconnect_pdf', __('DPD Connect Download Labels', 'dpdconnect'), [self::class, 'render'], 'shop_order', 'side', 'high');
    }

    public static function render()
    {
        global $post;

        $order = wc_get_order($post->ID);

        $labelRepo = new Label();
        $shippingLabel = $labelRepo->getByOrderId($order->get_id(), ParcelType::TYPEREGULAR);
        $returnLabel = $labelRepo->getByOrderId($order->get_id(), ParcelType::TYPERETURN);

        echo '<table>';

        if (!$shippingLabel && !$returnLabel) {
            echo '<tr><td>' . __('No labels available.') . '</a></td></tr>';
        }

        if ($shippingLabel) {
            $shippingId = $shippingLabel['id'];
            $shippingUrl = add_query_arg(['plugin' => 'dpdconnect', 'file' => 'shipping_label', 'id' => $shippingId], admin_url());
            echo '<tr><td><a href="' . $shippingUrl . '">' . __('Shipping label', 'dpdconnect') . '</a></td>';
            echo '<td>' . $shippingLabel['created_at'] . '</td></tr>';
        }

        if ($returnLabel) {
            $returnId = $returnLabel['id'];
            $returnUrl = add_query_arg(['plugin' => 'dpdconnect', 'file' => 'shipping_label', 'id' => $returnId], admin_url());
            echo '<tr><td><a href="' . $returnUrl . '">' . __('Return label', 'dpdconnect') . '</a></td>';
            echo '<td>' . $returnLabel['created_at'] . '</td></tr>';
        }

        echo '</table>';
    }
}
