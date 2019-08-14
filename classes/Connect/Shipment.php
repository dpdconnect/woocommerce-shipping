<?php

namespace DpdConnect\classes\Connect;

use Exception;
use DpdConnect\classes\Option;
use DpdConnect\classes\Database\Job;
use DpdConnect\classes\Database\Label;
use DpdConnect\classes\Database\Batch;
use DpdConnect\classes\Handlers\Notice;
use DpdConnect\classes\enums\NoticeType;
use DpdConnect\classes\enums\ParcelType;
use DpdConnect\classes\Handlers\Callback;
use DpdConnect\classes\OrderResponseTransformer;
use DpdConnect\classes\Exceptions\InvalidResponseException;
use DpdConnect\Sdk\Exceptions\HttpException;
use DpdConnect\Sdk\Exceptions\RequestException;
use DpdConnect\Sdk\Exceptions\AuthenticateException;

class Shipment extends Connection
{
    private $batch;

    private $label;

    public function __construct()
    {
        parent::__construct();

        $this->job = new Job();
        $this->batch = new Batch();
        $this->label = new Label();
    }

    public function create($shipments, $map, $type = ParcelType::TYPEREGULAR)
    {
        $request = [
            'printOptions' => $this->printOptions(),
            'createLabel' => true,
            'shipments' => [],
        ];

        $request['shipments'] = $shipments;

        try {
            $labels = $this->client->getShipment()->create($request);
            if ($labels->getStatus() >= 300) {
                $error = $labels->getContent()['message'];
                Notice::add($error, NoticeType::ERROR);
                throw new Exception($error);
            }
            $labelResponses = $labels->getContent()['labelResponses'];
            foreach ($labelResponses as $labelResponse) {
                $parcelNumbers = implode(',', $labelResponse['parcelNumbers']);
                $this->label->create($labelResponse['orderId'], $labelResponse['label'], $type, $labelResponse['shipmentIdentifier'], $parcelNumbers);
            }
            return $labels;
        } catch (RequestException $e) {
            foreach ($e->getErrorDetails()->validation as $detail) {
                list($orderId, $simplePath) = OrderResponseTransformer::parseDetail($map, $detail);
                Notice::add('Order ' . $orderId . ': ' . __($detail['message'] . ' for ' . $simplePath), NoticeType::ERROR, true);
            }

            foreach ($e->getErrorDetails()->errors as $detail) {
                if (!is_array($detail)) {
                    Notice::add($detail, NoticeType::ERROR, true);
                } else {
                    try {
                        list($orderId, $simplePath) = OrderResponseTransformer::parseDetail($map, $detail);
                    } catch (InvalidResponseException $e) {
                        if (isset($detail['_embedded']['errors'][0]['message'])) {
                            $errorMessage = $detail['_embedded']['errors'][0]['message'];
                            Notice::add($errorMessage, NoticeType::ERROR, true);
                            continue;
                        }
                        Notice::add(__('Something went wrong at DPD Connect'), NoticeType::ERROR, true);
                        continue;
                    }

                    if (!isset($detail['_embedded']['errors'][0]['message'])) {
                        Notice::add(sprintf(__('Order %s: Something went wrong at DPD Connect'), $orderId), NoticeType::ERROR, true);
                        continue;
                    }
                    $errorMessage = $detail['_embedded']['errors'][0]['message'] . ' for ' . $simplePath;
                    Notice::add(sprintf(__('Order %s: %s'), $orderId, $errorMessage), NoticeType::ERROR, true);
                }
            }

            throw $e;
        } catch (HttpException $httpException) {
            Notice::add($httpException->getErrorDetails(), NoticeType::ERROR);
            throw $httpException;
        } catch (AuthenticateException $authenticateException) {
            Notice::add($authenticateException->getErrorDetails(), NoticeType::ERROR);
            throw $authenticateException;
        } catch (Exception $exception) {
            Notice::add($exception->getMessage(), NoticeType::ERROR);
            throw $exception;
        }
    }

    public function createAsync($shipments, $map, $type = ParcelType::TYPEREGULAR)
    {
        $request = [
            'callbackURI' => Callback::createUrl(),
            'label' => [
                'printOptions' => $this->printOptions(),
                'createLabel' => true,
                'shipments' => [],
            ],
        ];

        $request['label']['shipments'] = $shipments;

        try {
            $response = $this->client->getShipment()->createAsync($request);
            $batchId = $this->batch->create($shipments);

            if (isset($response->getContent()['message'])) {
                throw new Exception($response->getContent()['message']);
            }

            foreach ($response->getContent() as $key => $job) {
                $this->job->create($batchId, $job['jobid'], $shipments[$key]['orderId'], $type);
            }

            return $batchId;
        } catch (RequestException $e) {
            foreach ($e->getErrorDetails()->validation as $detail) {
                try {
                    list($orderId, $simplePath) = OrderResponseTransformer::parseAsyncDetail($map, $detail);
                    Notice::add('Order ' . $orderId . ': ' . __($detail['message'] . ' for ' . $simplePath), NoticeType::ERROR, true);
                } catch (InvalidResponseException $responseException) {
                    Notice::add(__('Reponse could not be parsed. Please contact customerit'), NoticeType::ERROR);
                    throw $e;
                }
            }

            foreach ($e->getErrorDetails()->errors as $detail) {
                Notice::add($detail, NoticeType::ERROR, true);
            }

            throw $e;
        } catch (HttpException $httpException) {
            Notice::add($httpException->getErrorDetails(), NoticeType::ERROR);
            throw $httpException;
        } catch (AuthenticateException $authenticateException) {
            Notice::add($authenticateException->getErrorDetails(), NoticeType::ERROR);
            throw $authenticateException;
        } catch (Exception $exception) {
            Notice::add($exception->getMessage(), NoticeType::ERROR);
            throw $exception;
        }
    }

    private function printOptions()
    {
        return [
            'printerLanguage' => 'PDF',
            'paperFormat' => Option::paperFormat(),
            'verticalOffset' => 0,
            'horizontalOffset' => 0,
        ];
    }
}
