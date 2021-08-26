<?php

namespace DpdConnect\classes\producttypes;



class B2B implements ProductTypeInterface
{

    public static function getProductType(): string
    {
        return 'b2b';
    }
}