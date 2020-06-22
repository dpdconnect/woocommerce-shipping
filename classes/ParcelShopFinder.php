<?php

namespace DpdConnect\classes;

use DpdConnect\classes\Gmaps;
use DpdConnect\classes\Option;
use DpdConnect\Sdk\ClientBuilder;
use DpdConnect\classes\Handlers\Notice;
use DpdConnect\classes\enums\NoticeType;
use DpdConnect\Sdk\Exceptions\RequestException;

class ParcelShopFinder
{
    private $transformer;
    private $validator;

    public function __construct()
    {
        $this->validator = new OrderValidator();
        $this->orderTransformer = new OrderTransformer($this->validator);
        $this->validator->validateOptions();

        if (!$this->validator->isValid()) {
            Notice::add(__('DPD Connect plugin configuration not finished'), NoticeType::ERROR);
            return;
        }

        $url = Option::connectUrl();
        $username = Option::connectUsername();
        $password = Option::connectPassword();
        $clientBuilder = new ClientBuilder($url);
        $this->dpdClient = $clientBuilder->buildAuthenticatedByPassword($username, $password);

        $this->client->getAuthentication()->setJwtToken(
            get_option('dpdconnect_jwt_token') ?: null
        );

        $this->dpdClient->getAuthentication()->setTokenUpdateCallback(function ($jwtToken) {
            update_option('dpdconnect_jwt_token', $jwtToken);
            $this->dpdClient->getAuthentication()->setJwtToken($jwtToken);
        });
    }

    public function getParcelShops($coordinates, $isocode)
    {
        $query = ['longitude'  => $coordinates['longitude'],
                  'latitude'   => $coordinates['latitude'],
                  'countryIso' => $isocode,
                  'consigneePickupAllowed' => true,
                  'limit' => 10,
        ];

        return $this->dpdClient->getParcelShop()->getList($query);
    }

    public function getGeoData($postalCode, $isoCode)
    {
        return Gmaps::getGeoData($postalCode, $isoCode);
    }
}
