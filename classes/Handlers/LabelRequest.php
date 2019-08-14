<?php

namespace DpdConnect\classes\Handlers;

use WC_Order;
use Exception;
use DpdConnect\classes\Option;
use DpdConnect\classes\OrderValidator;
use DpdConnect\classes\enums\NoticeType;
use DpdConnect\classes\enums\ParcelType;
use DpdConnect\classes\OrderTransformer;
use DpdConnect\classes\Connect\Shipment;
use DpdConnect\classes\Handlers\Download;
use DpdConnect\Sdk\Exceptions\RequestException;
use DpdConnect\classes\Exceptions\InvalidOrderException;

class LabelRequest
{
    public static function handle()
    {
        add_filter('handle_bulk_actions-edit-shop_order', [self::class, 'bulk'], 10, 3);
    }

    public static function single()
    {
        $orderId = isset($_GET['order_id']) ? $_GET['order_id'] : false;
        $return = isset($_GET['returnlabel']) ? $_GET['returnlabel'] : false;
        $parcelCount = isset($_GET['DPDlabelAmount']) ? $_GET['DPDlabelAmount'] : false;
        $validator = new OrderValidator();
        $orderTransformer = new OrderTransformer($validator);

        try {
            $shipment = $orderTransformer->createShipment($orderId, $return, $parcelCount);
        } catch (InvalidOrderException $e) {
            self::redirect();
        }

        $type = ParcelType::parse($return);
        $response = self::syncRequest([$shipment], [$orderId], $type);
        $labelContents = $response->getContent()['labelResponses'][0]['label'];
        $code = $response->getContent()['labelResponses'][0]['shipmentIdentifier'];

        return Download::pdf($labelContents, $code);
    }

    public static function bulk($redirect_to, $action, $post_ids)
    {
        if ($action !== 'dpdconnect_create_labels_bulk_action' && $action !== 'dpdconnect_create_return_labels_bulk_action') {
            return;
        }

        $type = ($action === 'dpdconnect_create_return_labels_bulk_action') ? ParcelType::TYPERETURN : ParcelType::TYPEREGULAR;
        $orderValidator = new OrderValidator();
        $orderTransformer = new OrderTransformer($orderValidator);
        $shipments = [];
        $map = [];

        foreach ($post_ids as $id) {
            $post = get_post($id);
            $currentOrder = new WC_Order($post->ID);
            $orderId = $currentOrder->get_id();
            $map[] = $orderId;
            try {
                if ($action === 'dpdconnect_create_labels_bulk_action') {
                    $shipments[] = $orderTransformer->createShipment($orderId);
                } elseif ($action === 'dpdconnect_create_return_labels_bulk_action') {
                    $shipments[] = $orderTransformer->createShipment($orderId, true);
                }
            } catch (InvalidOrderException $e) {
                self::redirect();
            }
        }

        if (count($post_ids) <= Option::asyncTreshold()) {
            $response = self::syncRequest($shipments, $map, $type);
            return Download::zip($response);
        }

        return self::asyncRequest($shipments, $map, $type);
    }

    private static function syncRequest($shipments, $map, $type = ParcelType::TYPEREGULAR)
    {
        $shipmentRepo = new Shipment();

        try {
            return $shipmentRepo->create($shipments, $map, $type);
        } catch (Exception $e) {
            self::redirect();
        }
    }

    private static function asyncRequest($shipments, $map, $type)
    {
        $shipmentRepo = new Shipment();
        $adminUrl = admin_url();

        try {
            $batchId = $shipmentRepo->createAsync($shipments, $map, $type);
            Notice::add(__('Labels succesfully requested'));
            wp_redirect($adminUrl . "admin.php?page=dpdconnect-jobs&batchId=$batchId");
            exit;
        } catch (Exception $e) {
            self::redirect();
        }
    }

    private static function redirect()
    {
        $redirect = isset($_GET['redirect_to']) ? base64_decode($_GET['redirect_to']) : admin_url() . 'edit.php?post_type=shop_order';
        wp_redirect($redirect);
        exit;
    }
}
