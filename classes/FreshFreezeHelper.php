<?php


namespace DpdConnect\classes;


class FreshFreezeHelper
{
    public static function doFreshFreezeRedirect($action, $postIds, $parcelCount = 1)
    {
        wp_redirect(sprintf(admin_url() . "admin.php?page=dpdconnect-fresh-freeze&%s&%s&%s",
            http_build_query(['label_type' => $action]),
            http_build_query(['order_ids' => $postIds]),
            http_build_query(['parcel_count' => $parcelCount])
        ));
        exit;
    }

    public static function checkOrdersContainFreshFreezeItems(array $orders)
    {
        /** @var \WC_Order $order */
        foreach ($orders as $order) {
            foreach ($order->get_items() as $orderItem) {
                $shippingProduct = get_post_meta($orderItem->get_product()->get_id(), 'dpd_shipping_product', true);

                if ($shippingProduct === TypeHelper::DPD_SHIPPING_PRODUCT_FRESH || $shippingProduct === TypeHelper::DPD_SHIPPING_PRODUCT_FREEZE) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function groupOrderItemsByShippingProduct(array $orders)
    {
        $groupedOrderItems = [];

        foreach ($orders as $order) {
            /** @var \WC_Order_Item $orderItem */
            foreach ($order->get_items() as $orderItem) {
                /** @var \WC_Product $product */
                $product = $orderItem->get_product();

                if (! $product) {
                    continue;
                }

                $shippingProduct = get_post_meta($product->get_id(), 'dpd_shipping_product', true);
                if($shippingProduct == '') {
                    $shippingProduct = 'default';
                }
                $groupedOrderItems[$order->get_id()][$shippingProduct][] = $orderItem;
            }
        }

        return $groupedOrderItems;
    }
}