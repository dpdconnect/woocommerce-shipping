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

    public static function single($postID, $type, $parcelCount)
    {
        $currentOrder = new WC_Order($postID);
        $orderId = $currentOrder->get_id();

        $validator = new OrderValidator();
        $orderTransformer = new OrderTransformer($validator);

        try {
            $shipment = $orderTransformer->createShipment($orderId, self::defineShipmentType($type, $orderId), $parcelCount);
        } catch (InvalidOrderException $e) {
            self::redirect();
        }

        $parcelType = (strpos($type, 'dpdconnect_create') != false) ? ParcelType::TYPERETURN : ParcelType::TYPEREGULAR;
        $response = self::syncRequest([$shipment], [$orderId], $parcelType);
        $labelContents = $response->getContent()['labelResponses'][0]['label'];
        $code = $response->getContent()['labelResponses'][0]['shipmentIdentifier'];

        return Download::pdf($labelContents, $code);
    }

    public static function bulk($redirect_to, $action, $post_ids)
    {
        if (strpos($action, 'dpdconnect_create') === false) {
            return;
        }

        $type = (strpos($action, 'dpdconnect_create') != false) ? ParcelType::TYPERETURN : ParcelType::TYPEREGULAR;

        $orderValidator = new OrderValidator();
        $orderTransformer = new OrderTransformer($orderValidator);
        $shipments = [];
        $map = [];
        $emailData = [];

        foreach ($post_ids as $id) {
            $post = get_post($id);
            $currentOrder = new WC_Order($post->ID);
            $orderId = $currentOrder->get_id();
            $emailData[$orderId] = [];
            $emailData[$orderId]['order'] = $currentOrder;
            $map[] = $orderId;
            try {
                $emailData[$orderId]['shipment'] = $orderTransformer->createShipment($orderId, self::defineShipmentType($action, $orderId));
                $shipments[] = $emailData[$orderId]['shipment'];
            } catch (InvalidOrderException $e) {
                self::redirect();
            }
        }

        if (count($post_ids) <= Option::asyncTreshold()) {
            $response = self::syncRequest($shipments, $map, $type);
            $labelResponses = $response->getContent()['labelResponses'];
            foreach ($labelResponses as $labelResponse) {
                if (isset($emailData[$labelResponse['orderId']])) {
                    $emailData[$labelResponse['orderId']]['parcelNumbers'] = $labelResponse['parcelNumbers'];
                }
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

    private static function defineShipmentType($type, $orderId)
    {
        switch ($type) {
            case 'dpdconnect_create_labels_bulk_action':
                $order = wc_get_order($orderId);
                $shippingMethods = $order->get_shipping_methods();
                foreach ($shippingMethods as $method) {
                    $settings = get_option('woocommerce_'.$method->get_method_id().'_'.$method->get_instance_id().'_settings');
                    if(!isset($settings['dpd_method_type'])) {
                        if(get_post_meta($orderId, '_dpd_parcelshop_id', true)) {
                            return 'parcelshop';
                        }
                        Notice::add(__('Shipping method has no DPD type'));
                        self::redirect();
                    }
                    return $settings['dpd_method_type'];
                }
                break;
            case 'dpdconnect_create_return_labels_bulk_action':
                return 'return';
                break;
            case 'dpdconnect_create_predict_labels_bulk_action':
                return 'predict';
                break;
            case 'dpdconnect_create_parcelshop_labels_bulk_action':
                if (!get_post_meta($orderId, '_dpd_parcelshop_id', true)) {
                    Notice::add(__('No ParcelShop shipping method was used for this order'));
                    self::redirect();
                }
                return 'parcelshop';
                break;
            case 'dpdconnect_create_classic_labels_bulk_action':
                return 'classic';
                break;
            case 'dpdconnect_create_saturday_labels_bulk_action':
                return 'saturday';
                break;
            case 'dpdconnect_create_express_10_labels_bulk_action':
                return 'express_10';
                break;
            case 'dpdconnect_create_express_12_labels_bulk_action':
                return 'express_12';
                break;
            case 'dpdconnect_create_express_18_labels_bulk_action':
                return 'express_18';
                break;
        }
    }

    private static function redirect()
    {
        $redirect = isset($_GET['redirect_to']) ? base64_decode($_GET['redirect_to']) : admin_url() . 'edit.php?post_type=shop_order';
        wp_redirect($redirect);
        exit;
    }
}
