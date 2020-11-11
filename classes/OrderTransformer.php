<?php

namespace DpdConnect\classes;

use DpdConnect\classes\Option;
use DpdConnect\classes\Exceptions\InvalidOrderException;

class OrderTransformer
{
    private $orderValidator;

    private $productInfo;

    public function __construct($validator)
    {
        $this->validator = $validator;

        $this->productInfo = new productInfo();
    }

    public function createShipment($orderId, $type, $parcelCount = 1)
    {
        $order = wc_get_order($orderId);

        $this->validator->validateReceiver($order, $orderId, $parcelCount);
        $shipment = [
            'orderId' => (string) $orderId,
            'sendingDepot' => Option::depot(),
            'sender' => [
                'name1' => Option::companyName(),
                'street' => Option::companyAddress(),
                'country' => strtoupper(Option::companyCountryCode()),
                'postalcode' => Option::companyPostalCode(),
                'city' => Option::companyCity(),
                'phoneNumber' => Option::companyPhone(),
                'email' => Option::companyEmail(),
                'commercialAddress' => true,
                'vat_number' => Option::vatNumber(),
                'eori_number' => Option::eoriNumber(),
            ],
            'receiver' => [
                'name1' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                'street' => $order->get_shipping_address_1() . $order->get_shipping_address_2(),
                'phoneNumber' => $order->get_billing_phone(),
                'country' => $order->get_shipping_country(),
                'postalcode' => $order->get_shipping_postcode(), // No spaces in zipCode!
                'city' => $order->get_shipping_city(),
                'commercialAddress' => false,
            ],
            'product' => [
                'productCode' => $this->getProductCode($type),
                'saturdayDelivery' => $this->getSaturdayDelivery($type),
                'homeDelivery' => $this->isHomeDelivery($type)
            ],
        ];

        if ($type != 'return') {
            if ($type === 'predict' ||
                $type === 'saturday') {
                $shipment['notifications'][] = [
                    'subject' => 'predict',
                    'channel' => 'EMAIL',
                    'value' => $order->billing_email,
                ];
            }

            if ($type === 'parcelshop') {
                $parcelShopId = get_post_meta($orderId, '_dpd_parcelshop_id', true);
                $shipment['product']['parcelshopId'] = $parcelShopId;
                $shipment['notifications'][] = [
                    'subject' => 'parcelshop',
                    'channel' => 'EMAIL',
                    'value' => $order->get_billing_email(),
                ];
            }
        }

        $shipment['parcels'] = [];
        $orderItems = $order->get_items();

        $totalWeight = @array_reduce($orderItems, function ($sum, $item) use ($orderId) {
            $product = wc_get_product($item['product_id']);
            $this->validator->validateProduct($product, $orderId);
            $sum += $product->get_weight() * $item->get_quantity();
            return $sum;
        });

        if(!$totalWeight) {
            $totalWeight = (int)Option::defaultProductWeight() * 100;
        }

        for ($x = 1; $x <= $parcelCount; $x++) {
            $shipment['parcels'][] = [
                'customerReferenceNumber1' => $orderId,
                'weight' => (int) ceil($totalWeight / $parcelCount),
            ];
        }

        $shipment = $this->addCustomsToShipment($shipment, $order);

        if (!$this->validator->isValid()) {
            throw new InvalidOrderException('Validation failed');
        }

        return $shipment;
    }

    private function addCustomsToShipment($shipment, $order)
    {
        $shipment['customs'] = [
            'terms' => 'DAP',
            'totalCurrency' => 'EUR',
        ];

        $totalAmount = 0.00;

        $rows = $order->get_items();
        $customsLines = [];

        foreach ($rows as $row) {
            $productId = $row['product_id'];
            $product = wc_get_product($productId);
            $hsCode = $this->productInfo->getHsCode($productId);
            $customsValue = $this->productInfo->getCustomsValue($product);
            $originCountry = $this->productInfo->getCountryOfOrigin($productId);

            $productWeight = (int)Option::defaultProductWeight() * 100; // Transforming kilo to deca
            $rowWeight = $productWeight * $row->get_quantity();

            $amount = $row->get_total();
            $totalAmount += $amount;
            $customsLines[] = [
                'description' => substr($product->get_name(), 0, 35),
                'harmonizedSystemCode' => $hsCode,
                'originCountry' => $originCountry,
                'quantity' => (int) $row->get_quantity(),
                'netWeight' => (int) ceil($rowWeight),
                'grossWeight' => (int) ceil($rowWeight),
                'totalAmount' => (double) ($amount),
            ];
        }

        $shipment['customs']['totalAmount'] = (double) $totalAmount;

        $consignee = [
            'name1' => Option::companyName(),
            'street' => Option::companyAddress(),
            'country' => strtoupper(Option::companyCountryCode()),
            'postalcode' => Option::companyPostalCode(),
            'city' => Option::companyCity(),
            'commercialAddress' => true,
        ];

        $consignor = [
            'name1' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
            'street' => $order->get_shipping_address_1(),
            'country' => $order->get_shipping_country(),
            'postalcode' => $order->get_shipping_postcode(), // No spaces in zipCode!
            'city' => $order->get_shipping_city(),
            'commercialAddress' => false,
            'sprn' => Option::smallParcelReference(),
        ];

        $shipment['customs']['customsLines'] = $customsLines;
        $shipment['customs']['consignee'] = $consignee;
        $shipment['customs']['consignor'] = $consignor;

        return $shipment;
    }

    private function isHomeDelivery($type)
    {
        if($type == 'predict' || $type == 'saturday') {
            return true;
        }

        return false;
    }

    private function getProductCode($type)
    {
        if ($type === 'return') {
            return 'RETURN';
        }

        if ($type === 'express_10') {
            return 'E10';
        }

        if ($type === 'express_12') {
            return 'E12';
        }

        if ($type === 'express_18') {
            return 'E18';
        }

        return 'CL';
    }

    private function getSaturdayDelivery($type)
    {
        if ($type === 'saturday') {
            return true;
        }

        return false;
    }
}
