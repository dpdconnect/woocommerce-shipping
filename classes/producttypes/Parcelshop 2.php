<?php

namespace DpdConnect\classes\producttypes;


class Parcelshop implements ProductTypeInterface
{

    public static function getProductType(): string
    {
        return 'parcelshop';
    }
}