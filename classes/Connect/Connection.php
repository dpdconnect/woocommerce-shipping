<?php

namespace DpdConnect\classes\Connect;

use DpdConnect\classes\Option;
use DpdConnect\classes\Version;
use DpdConnect\Sdk\ClientBuilder;
use DpdConnect\Sdk\Objects\MetaData;
use DpdConnect\Sdk\Objects\ObjectFactory;
use DpdConnect\classes\Connect\Cache;

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
        $this->client->getAuthentication()->setJwtToken(
            get_option('dpdconnect_jwt_token') ?: null
        );

        $this->client->getAuthentication()->setTokenUpdateCallback(function ($jwtToken) {
            update_option('dpdconnect_jwt_token', $jwtToken);
            $this->client->getAuthentication()->setJwtToken($jwtToken);
        });

        $this->client->setCacheCallable(new Cache());
    }
}
