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

    public static function sendTrackingEmail()
    {
        return self::parse(get_option('dpdconnect_general'), 'dpdconnect_send_trackingemail');
    }

    public static function downloadFormat()
    {
        return self::parse(get_option('dpdconnect_general'), 'dpdconnect_download_format');
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

    public static function customsTerms()
    {
        return self::parse(get_option('dpdconnect_company_info'), 'dpdconnect_customs_terms');
    }

    /**
     * PRODUCT SETTINGS
     */
    public static function defaultHsCode()
    {
        return self::parse(get_option('dpdconnect_products'), 'dpdconnect_default_hs_code');
    }

    /**
     * @return mixed|null
     */
    public static function defaultOriginCountry()
    {
        return self::parse(get_option('dpdconnect_products'), 'dpdconnect_default_origin_country');
    }

    /**
     * @return mixed|null
     */
    public static function defaultProductWeight()
    {
        return self::parse(get_option('dpdconnect_products'), 'dpdconnect_default_product_weight');
    }

    /**
     * @return mixed|null
     */
    public static function defaultPackageType()
    {
        return self::parse(get_option('dpdconnect_general'), 'dpdconnect_default_package_type');
    }

    /**
     * PARCELSHOP
     */

    public static function googleMapsApiKey()
    {
        return self::parse(get_option('dpdconnect_parcelshop'), 'dpdconnect_google_maps_api_key');
    }

    public static function useDpdGoogleMapsKey()
    {
        return (self::parse(get_option('dpdconnect_parcelshop'), 'dpdconnect_use_dpd_google_maps_api_key') == 'on');
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
