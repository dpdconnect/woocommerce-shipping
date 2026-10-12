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
    /**
     * cURL timeouts in seconds, passed to every SDK HTTP client we build.
     *
     * The plugin was locked on php-sdk 1.1.9, whose defaults were 100s total / 100s
     * connect, and 1000/1000 when the constructor was called without arguments. From
     * 1.1.14 onward the SDK defaults are 30s total / 2s connect. A synchronous label
     * request covers up to Option::MAX_ASYNC_TRESHOLD shipments and a slow host needs
     * more than two seconds to connect, so set the window here rather than inherit it.
     */
    private const HTTP_TIMEOUT = 300;
    private const HTTP_CONNECT_TIMEOUT = 10;

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

        $clientBuilder->setHttpClient(self::httpClient($url));

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
            self::httpClient(Option::connectUrl())
        );

        $userName = Option::connectUsername();

        $cacheWrapper = new CacheWrapper(new Cache());
        $cacheWrapper->storeCachedList(null, $userName, 'dpd_token');
        $token->setCacheWrapper($cacheWrapper);

        return $token->getPublicJWTToken($userName, Option::connectPassword());
    }

    /**
     * Build an SDK HTTP client with the plugin's timeouts instead of the SDK defaults.
     */
    public static function httpClient(string $url): HttpClient
    {
        return new HttpClient($url, self::HTTP_TIMEOUT, self::HTTP_CONNECT_TIMEOUT);
    }
}
