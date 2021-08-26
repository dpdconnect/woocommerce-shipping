<?php

namespace DpdConnect\classes\producttypes;


class Fresh implements ProductTypeInterface
{

    public static function getProductType(): string
    {
        return 'fresh';
    }
}