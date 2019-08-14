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

    public function createShipment($orderId, $return = false, $parcelCount = 1)
    {
        $order = wc_get_order($orderId);

        $shippingMethods = $order->get_shipping_methods();
        // Get the first item of the array. Todo: Strict mode in php7 does not agree with
        // the reset method due to array being passed as reference.
        $firstMethod = reset($shippingMethods);
        $shippingMethod = $firstMethod->get_method_id();

        $this->validator->validateReceiver($order, $orderId, $parcelCount);
        $shipment = [
            'orderId' => (string) $orderId,
            'smallParcelNumber' => Option::smallParcelReference(),
            'sendingDepot' => Option::depot(),
            'sender' => [
                'name1' => Option::companyName(),
                'street' => Option::companyAddress(),
                'country' => strtoupper(Option::companyCountryCode()),
                'postalcode' => Option::companyPostalCode(),
                'city' => Option::companyCity(),
                'phone' => Option::companyPhone(),
                'email' => Option::companyEmail(),
                'commercialAddress' => true,
                'vat_number' => Option::vatNumber(),
                'eori_number' => Option::eoriNumber(),
            ],
            'receiver' => [
                'name1' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                'street' => $order->get_shipping_address_1(),
                'country' => $order->get_shipping_country(),
                'postalcode' => $order->get_shipping_postcode(), // No spaces in zipCode!
                'city' => $order->get_shipping_city(),
                'commercialAddress' => false,
            ],
            'product' => [
                'productCode' => $this->getProductCode($shippingMethod, $return),
                'saturdayDelivery' => $this->getSaturdayDelivery($shippingMethod, $return),
            ],
        ];

        if (!$return) {
            if ($shippingMethod === 'dpd_predict' ||
                $shippingMethod === 'dpd_saturday') {
                $shipment['notifications'][] = [
                    'subject' => 'predict',
                    'channel' => 'EMAIL',
                    'value' => $order->billing_email,
                ];
            }

            if ($shippingMethod === 'dpd_pickup') {
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

        $totalWeight = array_reduce($orderItems, function ($sum, $item) use ($orderId) {
            $product = wc_get_product($item['product_id']);
            $this->validator->validateProduct($product, $orderId);
            $sum += $product->get_weight() * $item->get_quantity();
            return $sum;
        });

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

            $productWeight = $product->get_weight() * 10; // Transforming kilo to deca
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
        ];

        $shipment['customs']['customsLines'] = $customsLines;
        $shipment['customs']['consignee'] = $consignee;
        $shipment['customs']['consignor'] = $consignor;

        return $shipment;
    }

    private function getProductCode($shippingMethod, $return)
    {
        if ($return === true || $return === 1 || $return === '1') {
            return 'RETURN';
        }

        if ($shippingMethod === 'dpd_e10') {
            return 'E10';
        }

        if ($shippingMethod === 'dpd_e12') {
            return 'E12';
        }

        if ($shippingMethod === 'dpd_e18') {
            return 'E18';
        }

        return 'CL';
    }

    private function getSaturdayDelivery($shippingMethod, $returnLabel)
    {
        if ($returnLabel) {
            return false;
        }

        if ($shippingMethod === 'dpd_saturday') {
            return true;
        }

        return false;
    }
}
