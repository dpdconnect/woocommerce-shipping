<?php


namespace DpdConnect\classes;


use DpdConnect\classes\Connect\Product;

/**
 * Class TypeHelper
 * @package DpdConnect\classes
 *
 * IMPORTANT:
 * This class should not be used forever! It's used temporarily to get types of dpd products because the API doesn't supply them yet.
 */
class TypeHelper
{
    const DPD_SHIPPING_PRODUCT_DEFAULT = 'default';
    const DPD_SHIPPING_PRODUCT_FRESH = 'fresh';
    const DPD_SHIPPING_PRODUCT_FREEZE = 'freeze';

    public static function getProduct($name)
    {
        $product = new Product();

        foreach ($product->getAllowedProducts() as $allowedProduct) {
            if (self::isFresh($allowedProduct)) {
                if (stripos(strtolower($name), self::DPD_SHIPPING_PRODUCT_FRESH) !== false) {
                    return $allowedProduct;
                }
            }

            if (self::isFreeze($allowedProduct)) {
                if (strpos(strtolower($name), self::DPD_SHIPPING_PRODUCT_FREEZE) !== false) {
                    return $allowedProduct;
                }
            }
        }
    }

    public static function convertServiceToCode($dpdProduct)
    {
        if($dpdProduct['code'] === "6") {
            return 'B2C';
        } elseif ($dpdProduct['code'] === "AGE") {
            return 'B2C';
        }

        return $dpdProduct['code'];
    }


    public static function isParcelshop($dpdProduct)
    {
        return (strpos(strtolower($dpdProduct['type']), 'parcelshop') !== false);
    }

    public static function isPredict($dpdProduct)
    {
        return (strpos(strtolower($dpdProduct['type']), 'predict') !== false);
    }

    public static function isSaturday($dpdProduct)
    {
        return (strpos(strtolower($dpdProduct['code']), '6') !== false);
    }

    public static function isReturn($dpdProduct)
    {
        return (strpos(strtolower($dpdProduct['name']), 'return') !== false);
    }

    public static function isHomeDelivery($dpdProduct)
    {
        if (self::isPredict($dpdProduct) || self::isSaturday($dpdProduct)) {
            return true;
        }

        return false;
    }

    public static function isFresh($dpdProduct)
    {
        return (stripos(strtolower($dpdProduct['type']), self::DPD_SHIPPING_PRODUCT_FRESH) !== false);
    }

    public static function isFreeze($dpdProduct)
    {
        return (stripos(strtolower($dpdProduct['type']), self::DPD_SHIPPING_PRODUCT_FREEZE) !== false);
    }
}