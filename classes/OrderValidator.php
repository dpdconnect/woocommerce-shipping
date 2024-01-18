<?php

namespace DpdConnect\classes;

use DpdConnect\classes\Option;
use DpdConnect\classes\Connect\Country;
use DpdConnect\classes\Handlers\Notice;
use DpdConnect\classes\enums\NoticeType;

class OrderValidator
{
    private bool $valid = true;

    private ProductInfo $productInfo;
    protected Country $country;

    public function __construct()
    {
        $this->productInfo = new ProductInfo();
        $this->country = new Country();
    }

    public function isValid()
    {
        return $this->valid;
    }

    public function validateOptions()
    {
        if (!Option::companyName()) {
            $error = __('Configure your company name in DPD Connect settings', 'dpdconnect');
            $this->flash($error);
            $this->valid = false;
        }

        if (!Option::companyAddress()) {
            $error = __('Configure your company address in DPD Connect settings', 'dpdconnect');
            $this->flash($error);
            $this->valid = false;
        }

        if (!Option::companyCountryCode()) {
            $error = __('Configure your company country code in DPD Connect settings', 'dpdconnect');
            $this->flash($error);
            $this->valid = false;
        }

        if (!Option::companyPostalCode()) {
            $error = __('Configure your company postal code in DPD Connect settings', 'dpdconnect');
            $this->flash($error);
            $this->valid = false;
        }

        if (!Option::companyCity()) {
            $error = __('Configure your company city in DPD Connect settings', 'dpdconnect');
            $this->flash($error);
            $this->valid = false;
        }

        if (!Option::companyPhone()) {
            $error = __('Configure your company phone in DPD Connect settings', 'dpdconnect');
            $this->flash($error);
            $this->valid = false;
        }

        if (!Option::companyEmail()) {
            $error = __('Configure your company email in DPD Connect settings', 'dpdconnect');
            $this->flash($error);
            $this->valid = false;
        }

        if (!Option::depot()) {
            $error = __('Configure your depot in DPD Connect settings', 'dpdconnect');
            $this->flash($error);
            $this->valid = false;
        }
    }

    public function validateReceiver($order, $orderId, $parcelCount)
    {
        if (!$order->get_shipping_first_name()) {
            $error = __('First name is required for sender', 'dpdconnect');
            $this->flash($error, $orderId);
            $this->valid = false;
        }

        if (!$order->get_shipping_last_name()) {
            $error = __('Last name is required for sender', 'dpdconnect');
            $this->flash($error, $orderId);
            $this->valid = false;
        }

        if (!$order->get_shipping_address_1()) {
            $error = __('Address is required for sender', 'dpdconnect');
            $this->flash($error, $orderId);
            $this->valid = false;
        }

        if (!$order->get_shipping_country()) {
            $error = __('Country is required for sender', 'dpdconnect');
            $this->flash($error, $orderId);
            $this->valid = false;
        }

        if (!$order->get_shipping_city()) {
            $error = __('City is required for sender', 'dpdconnect');
            $this->flash($error, $orderId);
            $this->valid = false;
        }

        if (!$order->get_shipping_postcode()) {
            $error = __('Postalcode is required for sender', 'dpdconnect');
            $this->flash($error, $orderId);
            $this->valid = false;
        }
    }


    public function validateProduct($product, $orderId)
    {
        if (!$product->get_name()) {
            /* translators: %s: ID of product */
            $error = sprintf(__('Product name is required on all products. Check product with ID %s', 'dpdconnect'), $product->get_id());
            $this->flash($error, $orderId);
            $this->valid = false;
        }

        if (!$this->productInfo->getCountryOfOrigin($product->get_id())) {
            /* translators: 1: Name of product 2: ID of product */
            $error = sprintf(__('Product country of origin is missing for: %1$s . Either set a country for <a href="wp-admin/post.php?post=%2$s&action=edit">this product</a> in its shipping attributes or configure a <a href="/wp-admin/admin.php?page=dpdconnect">default country</a> for your entire shop.', 'dpdconnect'), $product->get_name(), $product->get_id());
            $this->flash($error, $orderId);
            $this->valid = false;
        }
    }

    private function flash($error, $orderId = null)
    {
        if ($orderId) {
            Notice::add('Order ' . $orderId . ': ' . $error, NoticeType::ERROR, true);
            return;
        }

        Notice::add($error, NoticeType::ERROR, true);
    }
}
