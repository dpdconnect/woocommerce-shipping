<?php

namespace DpdConnect\classes;

use DpdConnect\classes\Option;

class ProductInfo
{
    public function getHsCode($productId)
    {
        $product = wc_get_product($productId);
        $hsCode = $product->get_meta('dpd_hs_code');

        if ($hsCode === "") {
            $hsCode = Option::defaultHsCode();
        }

        return $hsCode;
    }

    /**
     * @param \WC_Product $product
     * @return array|mixed|string
     */
    public function getCustomsValue($product)
    {
        $customsValue = $product->get_meta('dpd_customs_value');

        if ($customsValue === "") {
            $customsValue = $product->get_price();
        }

        return $customsValue;
    }

    public function getCountryOfOrigin($productId)
    {
        $product = wc_get_product($productId);
        $originCountry = $product->get_meta('dpd_origin_country');

        if ($originCountry === "") {
            $originCountry = Option::defaultOriginCountry();
        }

        return $originCountry;
    }
}
