<?php

namespace DpdConnect\classes\enums;

class ParcelType
{
    const TYPEREGULAR = 1;
    const TYPERETURN = 2;

    public static function parse($return)
    {
        if ($return) {
            return self::TYPERETURN;
        }

        return self::TYPEREGULAR;
    }
}
