<?php

namespace DpdConnect\classes\Handlers;

use DpdConnect\classes\Connect\Product;
use Exception;
use DpdConnect\classes\Option;
use DpdConnect\Sdk\ClientBuilder;
use DpdConnect\classes\enums\JobStatus;
use DpdConnect\Sdk\Exceptions\RequestException;
use DpdConnect\classes\Database\Job;
use DpdConnect\classes\Database\Batch;
use DpdConnect\classes\Database\Order;
use DpdConnect\classes\Database\Label as DatabaseLabel;
use DpdConnect\classes\Connect\Label as ConnectLabel;

class Callback
{
    public static function handle()
    {
        add_action('admin_post_nopriv_dpdbatch', [self::class, 'listen']);
    }

    public static function listen()
    {
        $incomingData = json_decode(file_get_contents('php://input'), true);

        $state = $incomingData['state'];

        /**
         * At this point, we do not care about the callback having been
         * fired
         */
        if ($state >= 16) {
            $state -= 16;
        }

        if ($state === 4) {
            self::success($incomingData);
        }

        if ($state >= 8) {
            self::failure($incomingData);
        }

        return;
    }

    public static function createUrl()
    {
        $baseUrl = Option::callbackUrl();
        if ($baseUrl === "" || is_null($baseUrl)) {
            $baseUrl = admin_url();
        }

        $action = 'dpdbatch';
        $url = $baseUrl . "admin-post.php?action=$action";

        return $url;
    }

    private static function success($incomingData)
    {
        $orderId = $incomingData['shipment']['orderId'];
        $externalId = $incomingData['jobid'];
        $parcelNumber = $incomingData['shipment']['trackingInfo']['parcelNumbers'][0];
        $shipmentIdentifier = $incomingData['shipment']['trackingInfo']['shipmentIdentifier'];
        $parcelNumbers = implode(',', $incomingData['shipment']['trackingInfo']['parcelNumbers']);

        $jobRepo = new Job();
        $batchRepo = new Batch();
        $job = $jobRepo->getByExternalId($externalId);

        try {
            $connectLabel = new ConnectLabel();
            $databaseLabel = new DatabaseLabel();
            $label = $connectLabel->get($parcelNumber);
            $labelId = $databaseLabel->create($orderId, $label, $job['type'], $shipmentIdentifier, $parcelNumbers);

            $jobStatus = $job['status'];

            $jobRepo->updateStatus($job, JobStatus::STATUSSUCCESS, null, null, $labelId);
            $batchRepo->updateStatus($job);

            $order = wc_get_order($orderId);

            add_post_meta($orderId, 'dpd_tracking_numbers', $incomingData['shipment']['trackingInfo']['parcelNumbers']);

            if ('enabled' == Option::sendTrackingEmail() && $order && $jobStatus === JobStatus::STATUSQUEUED) {
                self::sendMail($order, $incomingData['shipment']);
            }
        } catch (Exception $e) {
            $error = __('Could not download label after job completion.');
            $jobRepo->updateStatus($job, JobStatus::STATUSREQUEST, $error);
        }
    }

    private static function failure($incomingData)
    {
        $externalId = $incomingData['jobid'];

        $jobRepo = new Job();
        $batchRepo = new Batch();
        $job = $jobRepo->getByExternalId($externalId);
        $errors = $incomingData['error'];
        $stateMessage = $incomingData['stateMessage'];
        $jobRepo->updateStatus($job, JobStatus::STATUSFAILED, $stateMessage, $errors);
        $batchRepo->updateStatus($job);
    }

    private static function sendMail($order, $shipment)
    {
        $product = new Product();
        $dpdProduct = $product->getProductByCode($shipment['product']['productCode']);

        $emailData[$order->get_id()]['order'] = $order;

        $emailData[$order->get_id()]['shipment'] = $shipment;
        $emailData[$order->get_id()]['shipmentType'] = $dpdProduct['type'];
        $emailData[$order->get_id()]['parcelNumbers'] = $shipment['trackingInfo']['parcelNumbers'];

        LabelRequest::sendTrackingMail($emailData);
    }
}
