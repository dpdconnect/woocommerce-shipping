<?php

namespace DpdConnect\classes\Connect;

use DpdConnect\classes\Option;
use DpdConnect\classes\Version;
use DpdConnect\Sdk\Client;
use DpdConnect\Sdk\ClientBuilder;
use DpdConnect\Sdk\Common\HttpClient;
use DpdConnect\Sdk\Exceptions\AuthenticateException;
use DpdConnect\Sdk\Exceptions\HttpException;
use DpdConnect\Sdk\Exceptions\ServerException;
use DpdConnect\Sdk\Objects\MetaData;
use DpdConnect\Sdk\Objects\ObjectFactory;
use DpdConnect\Sdk\Resources\Token;
use DpdConnect\Sdk\CacheWrapper;

class Connection
{
    /** @var Client|null  */
    protected ?Client $client;

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

    /**
     * @throws AuthenticateException
     * @throws HttpException|ServerException
     */
    public static function getPublicJwtToken()
    {
        $token = new Token(
            new HttpClient(Option::connectUrl())
        );

        $userName = Option::connectUsername();

        $cacheWrapper = new CacheWrapper(new Cache());
        $cacheWrapper->storeCachedList(null, $userName, 'dpd_token');
        $token->setCacheWrapper($cacheWrapper);

        return $token->getPublicJWTToken($userName, Option::connectPassword());
    }
}
