<?php

namespace DpdConnect\classes;

class Option
{
    const MAX_ASYNC_TRESHOLD = 10;

    private static function parse($array, $item)
    {
        return isset($array[$item]) ? $array[$item] : null;
    }

    /**
     * GENERAL
     */
    public static function accountType()
    {
        return self::parse(get_option('dpdconnect_general'), 'dpdconnect_account_type');
    }

    public static function depot()
    {
        return self::parse(get_option('dpdconnect_general'), 'dpdconnect_depot');
    }

    public static function paperFormat()
    {
        return self::parse(get_option('dpdconnect_general'), 'dpdconnect_label_format');
    }

    /**
     * USER CREDENTIALS
     */
    public static function connectUsername()
    {
        return self::parse(get_option('dpdconnect_user_credentials'), 'dpdconnect_connect_username');
    }

    public static function connectPassword()
    {
        return self::parse(get_option('dpdconnect_user_credentials'), 'dpdconnect_connect_password');
    }

    /**
     * SHIPPING ADDRESS
     */
    public static function companyName()
    {
        return self::parse(get_option('dpdconnect_company_info'), 'dpdconnect_company_name');
    }

    public static function companyAddress()
    {
        return self::parse(get_option('dpdconnect_company_info'), 'dpdconnect_company_address');
    }

    public static function companyCountryCode()
    {
        return self::parse(get_option('dpdconnect_company_info'), 'dpdconnect_company_country_code');
    }

    public static function companyPostalCode()
    {
        return self::parse(get_option('dpdconnect_company_info'), 'dpdconnect_company_postal_code');
    }

    public static function companyCity()
    {
        return self::parse(get_option('dpdconnect_company_info'), 'dpdconnect_company_city');
    }

    public static function companyPhone()
    {
        return self::parse(get_option('dpdconnect_company_info'), 'dpdconnect_company_phone');
    }

    public static function companyEmail()
    {
        return self::parse(get_option('dpdconnect_company_info'), 'dpdconnect_company_email');
    }

    public static function vatNumber()
    {
        return self::parse(get_option('dpdconnect_company_info'), 'dpdconnect_vat_number');
    }

    public static function eoriNumber()
    {
        return self::parse(get_option('dpdconnect_company_info'), 'dpdconnect_eori_number');
    }

    public static function smallParcelReference()
    {
        return self::parse(get_option('dpdconnect_company_info'), 'dpdconnect_spr');
    }

    /**
     * PRODUCT SETTINGS
     */
    public static function defaultHsCode()
    {
        return self::parse(get_option('dpdconnect_products'), 'dpdconnect_default_hs_code');
    }

    public static function defaultOriginCountry()
    {
        return self::parse(get_option('dpdconnect_products'), 'dpdconnect_default_origin_country');
    }

    public static function defaultProductWeight()
    {
        return self::parse(get_option('dpdconnect_products'), 'dpdconnect_default_product_weight');
    }

    /**
     * PARCELSHOP
     */
    public static function googleMapsApiClientKey()
    {
        return self::parse(get_option('dpdconnect_parcelshop'), 'dpdconnect_google_maps_api_client_key');
    }

    public static function googleMapsApiServerKey()
    {
        return self::parse(get_option('dpdconnect_parcelshop'), 'dpdconnect_google_maps_api_server_key');
    }


    /**
     * ADVANCED
     */
    public static function connectUrl()
    {
        return self::parse(get_option('dpdconnect_advanced'), 'dpdconnect_connect_url');
    }

    public static function callbackUrl()
    {
        return self::parse(get_option('dpdconnect_advanced'), 'dpdconnect_callback_url');
    }

    public static function asyncTreshold()
    {
        $treshold = self::parse(get_option('dpdconnect_advanced'), 'dpdconnect_async_treshold');

        if (!$treshold) {
            return self::MAX_ASYNC_TRESHOLD;
        }

        if ($treshold > self::MAX_ASYNC_TRESHOLD) {
            return self::MAX_ASYNC_TRESHOLD;
        }

        return $treshold;
    }
}
