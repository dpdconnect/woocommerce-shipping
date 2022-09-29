<?php

namespace DpdConnect\classes\Handlers;

use DpdConnect\classes\Connect\Product;
use DpdConnect\classes\FreshFreezeHelper;
use DpdConnect\classes\producttypes\Fresh;
use DpdConnect\classes\producttypes\Parcelshop;
use DpdConnect\classes\TypeHelper;
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

    public static function single($postID, $type, $parcelCount, $freshFreezeData = [])
    {
        $currentOrder = new WC_Order($postID);
        $orderId = $currentOrder->get_id();

        $validator = new OrderValidator();
        $orderTransformer = new OrderTransformer($validator);

        if (FreshFreezeHelper::checkOrdersContainFreshFreezeItems([$currentOrder])) {
            // Gather dates for fresh/freeze items
            if (empty($freshFreezeData)) {
                FreshFreezeHelper::doFreshFreezeRedirect($type, [$postID], $parcelCount);
            }
        }

        $groupedOrderItems = FreshFreezeHelper::groupOrderItemsByShippingProduct([$currentOrder]);
        $shipments = [];
        $map = [];
        foreach ($groupedOrderItems[$currentOrder->get_id()] as $shippingProduct => $orderItems) {
            try {
                $shipmentParcelCount = $parcelCount;
                $dpdProduct = self::getDpdProduct($type, $orderId);

                if ($shippingProduct === TypeHelper::DPD_SHIPPING_PRODUCT_FRESH || $shippingProduct === TypeHelper::DPD_SHIPPING_PRODUCT_FREEZE) {
                    // Get specific FRESH or FREEZE Product
                    $dpdProduct = TypeHelper::getProduct($shippingProduct);
                    $shipmentParcelCount = count($freshFreezeData[$orderId][$shippingProduct]);
                }

                $map[] = $orderId;
                $shipments[] = $orderTransformer->createShipment(
                    $orderId,
                    $dpdProduct,
                    $shipmentParcelCount,
                    $orderItems,
                    $shippingProduct,
                    $freshFreezeData
                );
                $emailData[$orderId]['shipmentType'] = $dpdProduct['type'];
            } catch (InvalidOrderException $e) {
                self::redirect()->$e;
            }
        }

        $parcelType = (strpos($type, 'dpdconnect_create') != false) ? ParcelType::TYPERETURN : ParcelType::TYPEREGULAR;
        $response = self::syncRequest($shipments, $map, $parcelType);
        $labelContents = $response->getContent()['labelResponses'][0]['label'];
        $code = $response->getContent()['labelResponses'][0]['shipmentIdentifier'];

        if (count($response->getContent()['labelResponses']) > 1) {

            return Download::zip($response);
        }
        foreach ($response->getContent()['labelResponses'][0]['parcelNumbers'] as $parcelNumber) {
            add_post_meta($orderId, 'dpd_tracking_numbers', array($parcelNumber));
        }

        return Download::pdf($labelContents, $code);
    }

    public static function bulk($redirect_to, $action, $post_ids, $freshFreezeData = [])
    {
        if (strpos($action, 'dpdconnect_create') === false) {
            return;
        }

        $orders = [];
        foreach ($post_ids as $post_id) {
            $orders[] = wc_get_order($post_id);
        }

        if (FreshFreezeHelper::checkOrdersContainFreshFreezeItems($orders)) {
            // Gather dates for fresh/freeze items
            if (empty($freshFreezeData)) {
                FreshFreezeHelper::doFreshFreezeRedirect($action, $post_ids);
            }
        }

        $type = (strpos($action, 'dpdconnect_create') != false) ? ParcelType::TYPERETURN : ParcelType::TYPEREGULAR;

        $orderValidator = new OrderValidator();
        $orderTransformer = new OrderTransformer($orderValidator);
        $shipments = [];
        $map = [];
        $emailData = [];

        $groupedOrderItems = FreshFreezeHelper::groupOrderItemsByShippingProduct($orders);

        foreach ($orders as $currentOrder) {
            $orderId = $currentOrder->get_id();
            $emailData[$orderId] = [];
            $emailData[$orderId]['order'] = $currentOrder;
            $map[] = $orderId;

            foreach ($groupedOrderItems[$orderId] as $shippingProduct => $orderItems) {
                try {
                    $dpdProduct = self::getDpdProduct($action, $orderId);
                    $parcelCount = 1;

                    if ($shippingProduct === TypeHelper::DPD_SHIPPING_PRODUCT_FRESH || $shippingProduct === TypeHelper::DPD_SHIPPING_PRODUCT_FREEZE) {
                        // Get specific FRESH or FREEZE Product
                        $dpdProduct = TypeHelper::getProduct($shippingProduct);
                        $parcelCount = count($freshFreezeData[$orderId][$shippingProduct]);
                    }

                    $emailData[$orderId]['shipment'] = $orderTransformer->createShipment(
                        $orderId,
                        $dpdProduct,
                        $parcelCount,
                        $orderItems,
                        $shippingProduct,
                        $freshFreezeData
                    );
                    $shipments[] = $emailData[$orderId]['shipment'];
                } catch (InvalidOrderException $e) {
                    self::redirect()->$e;
                }
            }
        }

        if (count($post_ids) <= Option::asyncTreshold()) {
            $response = self::syncRequest($shipments, $map, $type);
            $labelResponses = $response->getContent()['labelResponses'];
            foreach ($labelResponses as $labelResponse) {
                if (isset($emailData[$labelResponse['orderId']])) {
                    $emailData[$labelResponse['orderId']]['parcelNumbers'] = $labelResponse['parcelNumbers'];
                }
                add_post_meta($labelResponse['orderId'], 'dpd_tracking_numbers', $labelResponse['parcelNumbers']);
            }

            if ('enabled' == Option::sendTrackingEmail()) {
                foreach ($emailData as $orderId => $data) {
                    $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $data['shipment']['sender']['name1'] . ' <' . $data['shipment']['sender']['email'] . '>');
                    ob_start();
                    include(plugin_dir_path(__FILE__) . "trackingemail" . DIRECTORY_SEPARATOR . "index.php");
                    $email_content = ob_get_contents();
                    ob_end_clean();
                    wp_mail($data['order']->get_billing_email(), __("Je bestelling is gereed voor verzending", 'dpdconnect'), $email_content, $headers);
                }
            }


            return Download::zip($response);
        }
        return self::asyncRequest($shipments, $map, $type);
    }

    function embed_images( &$phpmailer ) {
        $phpmailer->AddEmbeddedImage( plugin_dir_path(__FILE__)."trackingemail".DIRECTORY_SEPARATOR."DPD_logo_redgrad_rgb_responsive.svg", 'image1.svg' );
    }

    function add_attachments_to_php_mailer(&$phpmailer)
    {
        /* Required */
        $phpmailer->SMTPKeepAlive=true;
        $phpmailer->ContentType = "text/html";
        $phpmailer->From = strip_tags(get_option('admin_email'));
        $phpmailer->FromName = get_bloginfo('name');


        /* Add attachments to mail */
        global $global_attachments;
        foreach ($global_attachments as $attachment) {
            if (file_exists($attachment['path'])) {
                $phpmailer->AddEmbeddedImage($attachment['path'], $attachment['cid']);
            }
        }
    }

    function send_mail($to, $subject, $body, $headers = "", $attachments = [])
    {
        /* Must match the variable name used by "phpmailer_init" hook callback */
        global $global_attachments;

        /* Setup before sending email */
        $global_attachments = $attachments;
        add_action('phpmailer_init', 'add_attachments_to_php_mailer');

        /* Send Email */
        $is_sent = wp_mail($to, $subject, $body, $headers);

        /* Cleanup after email is sent */
        remove_action('phpmailer_init', 'add_attachments_to_php_mailer');
        $global_attachments = [];

        return $is_sent;
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

    private static function getDpdProduct($type, $orderId)
    {
        $product = new Product();

        if ($type === 'dpdconnect_create_labels_bulk_action') {
            $order = wc_get_order($orderId);
            $shippingMethods = $order->get_shipping_methods();
            foreach ($shippingMethods as $method) {
                $settings = get_option('woocommerce_'.$method->get_method_id().'_'.$method->get_instance_id().'_settings');
                if(!isset($settings['dpd_method_type'])) {
                    if(get_post_meta($orderId, '_dpd_parcelshop_id', true)) {
                        return $product->getAllowedProductsByType(Parcelshop::getProductType())[0];
                    }
                    Notice::add(__('Shipping method has no DPD type'));
                    self::redirect();
                }

                return $product->getProductByCode($settings['dpd_method_type']);
            }

            Notice::add(__('Order has no shipping method'));
            self::redirect();
        }

        $dpdProductCode = str_replace('dpdconnect_create_', '', $type);
        $dpdProductCode = str_replace('_labels_bulk_action', '', $dpdProductCode);

        $dpdProduct = $product->getProductByCode($dpdProductCode);

        if (! $dpdProduct) {
            Notice::add(__('DPD Product could not be found'));
            self::redirect();
        }

        // Check if a Parcelshop shipping method is used for this order
        if ($dpdProduct['type'] === Parcelshop::getProductType()) {
            if (!get_post_meta($orderId, '_dpd_parcelshop_id', true)) {
                Notice::add(__('No ParcelShop shipping method was used for this order'));
                self::redirect();
            }
        }

        return $dpdProduct;
    }

    public static function redirect()
    {
        $redirect = isset($_GET['redirect_to']) ? base64_decode($_GET['redirect_to']) : admin_url() . 'edit.php?post_type=shop_order';
        wp_redirect($redirect);
        exit;
    }
}
