<?php

namespace DpdConnect\classes\producttypes;


class Predict implements ProductTypeInterface
{

    public static function getProductType(): string
    {
        return 'predict';
    }
}