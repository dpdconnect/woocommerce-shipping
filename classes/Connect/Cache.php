<?php

namespace DpdConnect\classes\Connect;

use DpdConnect\Sdk\Resources\CacheInterface;

class Cache implements CacheInterface
{
    /**
     * @param string $name
     * @param $data
     * @param int $expire
     */
    public function setCache($name, $data, $expire)
    {
        set_transient($name, $data, $expire);
    }

    /**
     * @param string $name
     * @param $data
     * @param int $expire
     */
    public function getCache($name)
    {
        return get_transient($name);
    }
}