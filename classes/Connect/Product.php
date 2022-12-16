<?php

namespace DpdConnect\classes\Connect;

use DpdConnect\classes\Option;
use DpdConnect\classes\producttypes\B2B;
use DpdConnect\classes\producttypes\Fresh;
use DpdConnect\classes\producttypes\Parcelshop;
use DpdConnect\classes\producttypes\Predict;

class Product extends Connection
{
    public function getList()
    {
        if(!Option::connectUsername()) {
            return [];
        }

        try {
            $products = $this->client->getProduct()->getList();

            if(!is_array($products)) {  
                return [];
            }

            $products[] = [
                'name'=>'DPD Return',
                'description'=>'Added by plugin',
                'code'=>'RETURN',
                'additionalService'=>false,
                'type'=>'predict',
            ];
            return $products;
        } catch (\Exception $exception) {
           return [];
        }
    }

    public function getAllowedProducts()
    {
        $accountType = Option::accountType();
        $products = [];

            foreach ($this->getProductsByType(B2B::getProductType()) as $product) {
                $products[] = $product;
            }
            foreach ($this->getProductsByType(Predict::getProductType()) as $product) {
                $products[] = $product;
            }
            foreach ($this->getProductsByType(Parcelshop::getProductType()) as $product) {
                $products[] = $product;
            }

        foreach ($this->getProductsByType(Fresh::getProductType()) as $product) {
            $products[] = $product;
        }

        return $products;
    }

    public function getProductByCode(string $code)
    {
        foreach ($this->getAllowedProducts() as $product) {
            if ($product['code'] === $code) {
                return $product;
            }
        }

        return null;
    }

    public function getProductsByType(string $type)
    {
        return array_filter($this->getList(), function($product) use ($type) {
            return $product['type'] === $type;
        });
    }

    public function getAllowedProductsByType(string $type)
    {

        return array_filter($this->getAllowedProducts(), function($product) use ($type) {
            return $product['type'] === $type;
        });
    }
}
