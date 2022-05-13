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
        add_filter('manage_edit-shop_order_columns', [self::class, 'add']);
        add_action('manage_shop_order_posts_custom_column', [self::class, 'render']);
    }

    public static function add($columns)
    {
        $columns['dpdconnect_shipping_label'] = __('DPD Shipping label', 'dpdconnect');
        $columns['dpdconnect_return_label'] = __('DPD Return label', 'dpdconnect');
        $columns['dpdconnect_tracking_number'] = __('DPD Tracking numbers', 'dpdconnect');

        return $columns;
    }

    public static function render($column)
    {
        global $post;

        $order = wc_get_order($post->ID);

        $column === 'dpdconnect_shipping_label' ? self::columnShippingLabel($order) : null;
        $column === 'dpdconnect_return_label' ? self::columnReturnLabel($order) : null;
        $column === 'dpdconnect_tracking_number' ? self::columnTrackingNumber($order) : null;
    }

    private static function columnTrackingNumber($order)
    {
        $trackingCodes = get_post_meta($order->get_id(), 'dpd_tracking_numbers');
        if(empty($trackingCodes)) {
            return;
        }

        foreach ($trackingCodes as $trackingCode) {

            echo '<a target="_blank" href="https://tracking.dpd.de/status/en_US/parcel/' . $trackingCode[0] . '" title="' . __('Tracking numbers', 'dpdconnect') . '"><span>' . $trackingCode[0] . '</span></a><br/>';
        }
    }

    private static function columnShippingLabel($order)
    {
        $jobRepo = new Job();
        $job = $jobRepo->getByOrderId($order->get_id(), ParcelType::TYPEREGULAR);

        if(!empty($job)) {
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
        }
    }

    private static function columnReturnLabel($order)
    {

        $jobRepo = new Job();
        $job = $jobRepo->getByOrderId($order->get_id(), ParcelType::TYPERETURN);

        if(!empty($job)) {
            $jobId = $job['id'];
            $status = $job['status'];

            echo '<a href="' . admin_url() . 'admin.php?page=dpdconnect-job-details&jobId=' . $jobId . '" title="' . __('View job', 'dpdconnect') . '">' . JobStatus::tag($status) . '</a>';
        }

        $labelRepo = new Label();
        $returnLabel = $labelRepo->getByOrderId($order->get_id(), ParcelType::TYPERETURN);

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
        }
    }
}
