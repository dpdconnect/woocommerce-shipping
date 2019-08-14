<?php

namespace DpdConnect\classes;

use DpdConnect\classes\Exceptions\InvalidResponseException;

class OrderResponseTransformer
{
    /**
     * This method parses the index from the detail
     * node and returns it with a more readable path
     * e.g. body.shipments[0].sender.name1 becomes
     * '23' (a WooCommerce orderId) and 'sender name1'
     */
    public static function parseDetail($map, $detail)
    {
        $stringPath = 'body.shipments[';
        return self::parse($map, $detail, $stringPath);
    }

    public static function parseAsyncDetail($map, $detail)
    {
        $stringPath = 'body.label.shipments[';
        return self::parse($map, $detail, $stringPath);
    }

    private static function parse($map, $detail, $stringPath)
    {
        if (!isset($detail['dataPath'])) {
            if (isset($detail['_embedded']['errors'][0]['dataPath'])) {
                $path = $detail['_embedded']['errors'][0]['dataPath'];
            } else {
                throw new InvalidResponseException();
            }
        } else {
            $path = $detail['dataPath'];
        }

        if (!isset(explode($stringPath, $path)[1])) {
            throw new InvalidResponseException();
        }

        $first = explode($stringPath, $path)[1];
        $remains = explode(']', $first);

        // Find the matching order id
        $orderId = $map[$remains[0]];

        $position = strpos($path, '].') + 2;
        $pathString = substr($path, $position, strlen($path));

        return [$orderId, $pathString];
    }
}
