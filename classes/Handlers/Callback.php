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
        $allParcelNumbers = $incomingData['shipment']['trackingInfo']['parcelNumbers'];
        $shipmentIdentifier = $incomingData['shipment']['trackingInfo']['shipmentIdentifier'];
        $parcelNumbers = implode(',', $allParcelNumbers);

        $jobRepo = new Job();
        $batchRepo = new Batch();
        $job = $jobRepo->getByExternalId($externalId);

        // Idempotency: skip if this job was already successfully processed
        if ($job['status'] === JobStatus::STATUSSUCCESS) {
            return;
        }

        try {
            $connectLabel = new ConnectLabel();
            $databaseLabel = new DatabaseLabel();

            if (count($allParcelNumbers) === 1) {
                $label = $connectLabel->get($allParcelNumbers[0]);
            } else {
                $label = self::mergeParcelLabels($connectLabel, $allParcelNumbers);
            }

            $labelId = $databaseLabel->create($orderId, $label, $job['type'], $shipmentIdentifier, $parcelNumbers);

            $jobStatus = $job['status'];

            $jobRepo->updateStatus($job, JobStatus::STATUSSUCCESS, null, null, $labelId);
            $batchRepo->updateStatus($job);

            $order = wc_get_order($orderId);

            $order->update_meta_data('dpd_tracking_numbers', $allParcelNumbers);
            $order->save();

            if ('enabled' == Option::sendTrackingEmail() && $order && $jobStatus === JobStatus::STATUSQUEUED) {
                self::sendMail($order, $incomingData['shipment']);
            }
        } catch (Exception $e) {
            $error = __('Could not download label after job completion.');
            $jobRepo->updateStatus($job, JobStatus::STATUSREQUEST, $error);
        }
    }

    private static function mergeParcelLabels(ConnectLabel $connectLabel, array $parcelNumbers): string
    {
        require_once plugin_dir_path(__FILE__) . '../../vendor/myokyawhtun/pdfmerger/PDFMerger.php';
        $merger = new \PDFMerger\PDFMerger();
        $tmpFiles = [];

        foreach ($parcelNumbers as $parcelNumber) {
            $pdfBytes = base64_decode($connectLabel->get($parcelNumber));
            $tmp = tempnam(sys_get_temp_dir(), 'dpdpdf');
            file_put_contents($tmp, $pdfBytes);
            $merger->addPDF($tmp);
            $tmpFiles[] = $tmp;
        }

        $merged = $merger->merge('string');

        foreach ($tmpFiles as $tmp) {
            @unlink($tmp);
        }

        return base64_encode($merged);
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
