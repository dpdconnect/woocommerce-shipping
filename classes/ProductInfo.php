<?php

namespace DpdConnect\classes;

use DpdConnect\classes\Option;

class ProductInfo
{
    public function getHsCode($productId)
    {
        $hsCode = get_post_meta($productId, 'dpd_hs_code', true);

        if ($hsCode === "") {
            $hsCode = Option::defaultHsCode();
        }

        return $hsCode;
    }

    public function getCustomsValue($product)
    {
        $customsValue = get_post_meta($product->get_id(), 'dpd_customs_value', true);

        if ($customsValue === "") {
            $customsValue = $product->get_price();
        }

        return $customsValue;
    }

    public function getCountryOfOrigin($productId)
    {
        $originCountry = get_post_meta($productId, 'dpd_origin_country', true);

        if ($originCountry === "") {
            $originCountry = Option::defaultOriginCountry();
        }

        return $originCountry;
    }
}
