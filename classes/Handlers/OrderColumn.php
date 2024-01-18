<?php

namespace DpdConnect\classes\Handlers;

use DpdConnect\classes\Database\Job;
use DpdConnect\classes\Database\Label;
use DpdConnect\classes\enums\JobStatus;
use DpdConnect\classes\enums\ParcelType;

class OrderColumn
{
    public static function handle()
    {
        // For old WooCommerce versions
        add_filter('manage_edit-shop_order_columns', [self::class, 'add']);
        add_action('manage_shop_order_posts_custom_column', [self::class, 'render']);
        // For new WooCommerce versions
        add_filter('manage_woocommerce_page_wc-orders_columns', [self::class, 'add']);
        add_action('manage_woocommerce_page_wc-orders_custom_column', [self::class, 'render'], 10, 2);
    }

    public static function add($columns)
    {
        $columns['dpdconnect_shipping_label'] = __('DPD Shipping label', 'dpdconnect');
        $columns['dpdconnect_return_label'] = __('DPD Return label', 'dpdconnect');
        $columns['dpdconnect_tracking_number'] = __('DPD Tracking numbers', 'dpdconnect');

        return $columns;
    }

    public static function render($column, ?\WC_Order $order = null)
    {
        global $post;

        if (null !== $post) {
            // For old WooCommerce versions
            $order = wc_get_order($post->ID);
        }

        $column === 'dpdconnect_shipping_label' ? self::columnShippingLabel($order) : null;
        $column === 'dpdconnect_return_label' ? self::columnReturnLabel($order) : null;
        $column === 'dpdconnect_tracking_number' ? self::columnTrackingNumber($order) : null;
    }

    /**
     * @param \WC_Order $order
     * @return void
     */
    private static function columnTrackingNumber($order): void
    {
        $labelRepo = new Label();
        $shippingLabels = $labelRepo->getByOrderId($order->get_id(), ParcelType::TYPEREGULAR, true);

        if (true === empty($shippingLabels)) {
            echo '-';
            return;
        }

        // Only get the first 3 results if there are more than 3
        if (count($shippingLabels) > 3) {
            $shippingLabels = array_slice($shippingLabels, 0, 3);
        }

        foreach ($shippingLabels as $shippingLabel) {
            $parcelNumbers = explode(',', $shippingLabel['parcel_numbers']);
            $parcelNumber = $parcelNumbers[0];

            echo '<a target="_blank" href="https://www.dpdgroup.com/nl/mydpd/my-parcels/track?lang=en&parcelNumber=' . $parcelNumber . '" title="' . __('Tracking numbers', 'dpdconnect') . '"><span>' . $parcelNumber . '</span></a><br/>';
        }
    }

    /**
     * @param \WC_Order $order
     * @return void
     */
    private static function columnShippingLabel($order)
    {
        $jobRepo = new Job();
        $job = $jobRepo->getByOrderId($order->get_id(), ParcelType::TYPEREGULAR);

        if (false === empty($job)) {
            $jobId = $job['id'];
            $status = $job['status'];

            echo '<a href="' . admin_url() . 'admin.php?page=dpdconnect-job-details&jobId=' . $jobId . '" title="' . __('View job', 'dpdconnect') . '">' . JobStatus::Tag($status) . '</a>';
        }

        $labelRepo = new Label();
        $shippingLabel = $labelRepo->getByOrderId($order->get_id(), ParcelType::TYPEREGULAR);

        if ($shippingLabel) {
            $shippingId = $shippingLabel['id'];
            $shippingUrl = add_query_arg(
                [
                    'plugin' => 'dpdconnect',
                    'file' => 'shipping_label',
                    'id' => $shippingId,
                ],
                admin_url()
            );
            echo '<a href="' . $shippingUrl . '" title="' . __('Download PDF Label', 'dpdconnect') . '"><span class="dpdTag">' . __('PDF', 'dpdconnect') . '</span></a>';
        } else {
            echo '-';
        }

    }

    /**
     * @param \WC_Order $order
     * @return void
     */
    private static function columnReturnLabel($order)
    {
        $jobRepo = new Job();
        $job = $jobRepo->getByOrderId($order->get_id(), ParcelType::TYPERETURN);

        if (false === empty($job)) {
            $jobId = $job['id'];
            $status = $job['status'];

            echo '<a href="' . admin_url() . 'admin.php?page=dpdconnect-job-details&jobId=' . $jobId . '" title="' . __('View job', 'dpdconnect') . '">' . JobStatus::tag($status) . '</a>';
        }

        $labelRepo = new Label();
        $returnLabel = $labelRepo->getByOrderId($order->get_id(), ParcelType::TYPERETURN, true);

        if ($returnLabel) {
            $returnId = $returnLabel['id'];
            $returnUrl = add_query_arg(
                [
                    'plugin' => 'dpdconnect',
                    'file' => 'shipping_label',
                    'id' => $returnId,
                ],
                admin_url()
            );
            echo '<a href="' . $returnUrl . '" title="' . __('Download PDF Label', 'dpdconnect') . '"><span class="dpdTag">' . __('PDF', 'dpdconnect') . '</span></a>';
        } else {
            echo '-';
        }
    }
}
