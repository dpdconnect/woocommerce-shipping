<?php

namespace DpdConnect\classes;

use DpdConnect\classes\Option;
use DpdConnect\classes\Exceptions\InvalidOrderException;

class OrderTransformer
{
    private productInfo $productInfo;
    private mixed $validator;

    public function __construct($validator)
    {
        $this->validator = $validator;

        $this->productInfo = new productInfo();
    }

    public function createShipment($orderId, $dpdProduct, $parcelCount = 1, $orderItems = [], $shippingProduct = TypeHelper::DPD_SHIPPING_PRODUCT_DEFAULT, $freshFreezeData = [])
    {
        $order = wc_get_order($orderId);
        $orderItems = empty($orderItems) ? $order->get_items() : $orderItems;
        $this->validator->validateReceiver($order, $orderId, $parcelCount);
        $dpdProductCode = TypeHelper::convertServiceToCode($dpdProduct);

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
                'vatnumber' => Option::vatNumber(),
                'eorinumber' => str_replace('.','', Option::eoriNumber()),
            ],
            'receiver' => [
                'name1' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                'name2' => $order->get_shipping_company(),
                'street' => $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
                'email' => $order->get_billing_email(),
                'phoneNumber' => $order->get_billing_phone(),
                'country' => $order->get_shipping_country(),
                'postalcode' => $order->get_shipping_postcode(), // No spaces in zipCode!
                'city' => $order->get_shipping_city(),
                'commercialAddress' => false,
            ],
            'product' => [
                'productCode' => $dpdProductCode,
                'saturdayDelivery' => TypeHelper::isSaturday($dpdProduct),
                'homeDelivery' => TypeHelper::isHomeDelivery($dpdProduct),
                'ageCheck' => $this->isAgeCheckNeeded($orderItems)
            ],
        ];

        if (!TypeHelper::isReturn($dpdProduct)) {
            if (TypeHelper::isPredict($dpdProduct) || TypeHelper::isSaturday($dpdProduct)) {
                $shipment['notifications'][] = [
                    'subject' => 'predict',
                    'channel' => 'EMAIL',
                    'value' => $order->get_billing_email(),
                ];
            }

            if (TypeHelper::isParcelshop($dpdProduct)) {
                $parcelShopId = wc_get_order($orderId)->get_meta('_dpd_parcelshop_id');
                $shipment['product']['parcelshopId'] = $parcelShopId;
                $shipment['notifications'][] = [
                    'subject' => 'parcelshop',
                    'channel' => 'EMAIL',
                    'value' => $order->get_billing_email(),
                ];
            }
        }

        $shipment['parcels'] = [];

        $totalWeight = @array_reduce($orderItems, function ($sum, $item) use ($orderId) {
            $product = wc_get_product($item['product_id']);
            $this->validator->validateProduct($product, $orderId);
            $sum += (float)$product->get_weight() * $item->get_quantity();

            return $sum;
        });

        if(!$totalWeight) {
            $totalWeight = (int)Option::defaultProductWeight() * 100;
        } else {
            $totalWeight = $this->convertWeightToDpdWeight($totalWeight);
        }

        for ($x = 1; $x <= $parcelCount; $x++) {
            $shipment['parcels'][] = [
                'customerReferences' => [(string)$orderId],
                'weight' => (int) ceil($totalWeight / $parcelCount),
            ];
        }

        if ($shippingProduct === TypeHelper::DPD_SHIPPING_PRODUCT_FRESH || $shippingProduct === TypeHelper::DPD_SHIPPING_PRODUCT_FREEZE) {
            if (empty($freshFreezeData)) {
                throw new InvalidOrderException('No Fresh/Freeze data was supplied');
            }
            // Clear previous parcels
            $shipment['parcels'] = [];

            $shipment['parcels'] = $this->createFreshFreezeParcels(
                $orderItems,
                $order,
                ceil($totalWeight / $parcelCount),
                $shippingProduct,
                $freshFreezeData
            );
        }

        $shipment = $this->addCustomsToShipment($shipment, $order, $orderItems);

        if (!$this->validator->isValid()) {
            throw new InvalidOrderException('Validation failed');
        }

        return $shipment;
    }

    private function convertWeightToDpdWeight($weight)
    {
        $weightUnit = get_option('woocommerce_weight_unit');
        switch($weightUnit) {
            case 'kg':
                return $weight * 100;
                break;
            case 'g':
                return $weight / 10;
                break;
            case 'lbs':
                return $weight * 45.359237;
                break;
            case 'oz':
                return $weight * 2.834952313;
                break;
            default:
                return $weight;
        }
    }

    private function addCustomsToShipment($shipment, $order, $orderItems)
    {
        $shipment['customs'] = [
            // Use DAPDP as fallback when terms isn't set
            'terms' => Option::customsTerms() ?: 'DAPDP',
            'totalCurrency' => $order->get_currency(),
        ];

        $totalAmount = 0.00;

        $customsLines = [];
        foreach ($orderItems as $orderItem) {
            $productId = $orderItem['product_id'];
            $product = wc_get_product($productId);
            $hsCode = $this->productInfo->getHsCode($productId);
            $customsValue = $this->productInfo->getCustomsValue($product);
            $originCountry = $this->productInfo->getCountryOfOrigin($productId);

            $productWeight = (int)Option::defaultProductWeight() * 100; // Transforming kilo to deca
            $rowWeight = $productWeight * $orderItem->get_quantity();

            $amount = $orderItem->get_total();
            $totalAmount += $amount;
            $customsLines[] = [
                'description' => mb_substr($product->get_name(), 0, 35, 'utf-8'),
                'harmonizedSystemCode' => $hsCode,
                'originCountry' => $originCountry,
                'quantity' => (int) $orderItem->get_quantity(),
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

    private function createFreshFreezeParcels($orderItems, $order, $weight, $shippingProduct, $freshFreezeData)
    {
        $parcels = [];

        foreach ($orderItems as $orderItem) {
            /** @var \WC_Product $product */
            $product = $orderItem->get_product();

            $parcels[] = [
                'customerReferences' => [
                    (string)$order->get_id(),
                    $product->get_sku()
                ],
                'weight' => (int) $weight,
                'goodsExpirationDate' => (int)$freshFreezeData[$order->get_id()][$shippingProduct][$product->get_id()],
                'goodsDescription' => wc_get_order($product->get_id())->get_meta('dpd_carrier_description'),
            ];
        }

        return $parcels;
    }

    private function isAgeCheckNeeded($orderItems)
    {
        foreach($orderItems as $lineItem) {
            if(wc_get_product($lineItem['product_id'])->get_meta('dpd_age_check') === 'yes') {
                return true;
            }
        }

        return false;
    }
}
