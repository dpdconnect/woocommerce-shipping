<?php

namespace DpdConnect\classes\Connect;

use DpdConnect\classes\Option;
use DpdConnect\classes\Version;
use DpdConnect\Sdk\ClientBuilder;
use DpdConnect\Sdk\Objects\MetaData;
use DpdConnect\Sdk\Objects\ObjectFactory;

class Connection
{
    protected $client;

    public function __construct()
    {
        $username = Option::connectUsername();
        $password = Option::connectPassword();
        $url = Option::connectUrl();
        $clientBuilder = new ClientBuilder($url, ObjectFactory::create(MetaData::class, [
            'webshopType' => Version::type(),
            'webshopVersion' => Version::webshop(),
            'pluginVersion' => Version::plugin(),
        ]));
        $this->client = $clientBuilder->buildAuthenticatedByPassword($username, $password);
    }
}
