<?php

namespace DpdConnect\classes\Service;

use DpdConnect\classes\Connect\Connection;
use DpdConnect\classes\Option;
use DpdConnect\Sdk\Exceptions\AuthenticateException;
use DpdConnect\Sdk\Exceptions\HttpException;
use DpdConnect\Sdk\Exceptions\ServerException;

class SettingsDataValidator
{
    /** @var string  */
    const EMAIL_FORMAT_REGEX = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

    /**
     * @return array
     */
    public static function validateGeneralSettings(): array
    {
        $result['depot_number'] = self::validateDepot();

        return array_filter(
            $result,
            fn($value) => $value !== ''
        );
    }

    /**
     * @return array
     */
    public static function validateCredentialSettings(): array
    {
        $errors['username']       = self::validateUsername();
        $errors['password']       = self::validatePassword();
        $errors['authentication'] = self::authenticate();

        return array_filter(
            $errors,
            fn($value) => $value !== ''
        );
    }

    /**
     * @return array
     */
    public static function validateCompanySettings(): array
    {
        $errors['company']    = self::validateCompany();
        $errors['street']     = self::validateStreet();
        $errors['postalcode'] = self::validatePostalCode();
        $errors['city']       = self::validateCity();
        $errors['country']    = self::validateCountryCode();
        $errors['email']      = self::validateEmail();
        $errors['vatnumber']  = self::validateVatNumber();

        return array_filter(
            $errors,
            fn($value) => $value !== ''
        );
    }

    /**
     * @return array
     */
    public static function validateProductSettings(): array
    {
        $result['default_product_country_of_origin'] = self::validateDefaultProductCountryOfOriginCode();
        $result['harmonized_system_code']            = self::validateHarmonizedSystemCode();

        return array_filter(
            $result,
            fn($value) => $value !== ''
        );
    }

    /**
     * @return array
     */
    public static function validateUsername(): array
    {
        static $maxLength = 100;
        $userName = Option::connectUsername();

        $errors[] = self::validateLength($userName, $maxLength);
        return $errors;
    }

    /**
     * @return array
     */
    public static function validatePassword(): array
    {
        static $maxLength = 3000;
        $password = Option::connectPassword();

        $errors[] = self::validateLength($password, $maxLength);
        return $errors;
    }

    /**
     * @return array
     */
    private static function validateEmail(): array
    {
        static $maxLength = 50;

        $email = Option::companyEmail();
        $errors[] = self::validateLength($email, $maxLength);
        $errors[] = self::validateFormat($email, self::EMAIL_FORMAT_REGEX);

        return $errors;
    }

    /**
     * @return array
     */
    private static function validateCountryCode(): array
    {
        static $maxLength = 2;
        $countryCode = Option::companyCountryCode();

        $errors[] = self::validateLength($countryCode, $maxLength);
        return $errors;
    }

    /**
     * @return array
     */
    private static function validateDefaultProductCountryOfOriginCode(): array
    {
        static $maxLength = 2;
        $countryCode = Option::defaultOriginCountry();

        $errors[] = self::validateLength($countryCode, $maxLength);
        return $errors;
    }

    /**
     * @return array
     */
    private static function validatePostalCode(): array
    {
        static $maxLength = 9;
        $postalCode = Option::companyPostalCode();

        $errors[] = self::validateLength($postalCode, $maxLength);
        return $errors;
    }

    /**
     * @return array
     */
    private static function validateCity(): array
    {
        static $maxLength = 35;
        $city = Option::companyCity();

        $errors[] = self::validateLength($city, $maxLength);
        return $errors;
    }

    /**
     * @return array
     */
    private static function validateDepot(): array
    {
        static $maxLength = 4;
        static $minLength = 4;

        $depotNumber = Option::depot();

        $errors[] = self::validateLength($depotNumber, $maxLength, $minLength);
        return $errors;
    }

    /**
     * @return array
     */
    private static function validateCompany(): array
    {
        static $maxLength = 35;
        $company = Option::companyName();

        $errors[] = self::validateLength($company, $maxLength);
        return $errors;
    }

    /**
     * @return array
     */
    private static function validateStreet(): array
    {
        static $maxLength = 40;
        $address = Option::companyAddress();

        $errors[] = self::validateLength($address, $maxLength);
        return $errors;
    }

    /**
     * @return array
     */
    private static function validateVatNumber(): array
    {
        static $maxLength = 20;
        $vatNumber = Option::vatNumber();

        $errors[] = self::validateLength($vatNumber, $maxLength);
        return $errors;
    }

    /**
     * @return array
     */
    private static function validateHarmonizedSystemCode(): array
    {
        static $maxLength = 10;
        $company = Option::defaultHsCode();

        $errors[] = self::validateLength($company, $maxLength);
        return $errors;
    }

    /**
     * @return array
     */
    public static function authenticate(): array
    {
        $jwtToken = null;
        $errors = [];

        try {
            $jwtToken = Connection::getPublicJwtToken();
        } catch (AuthenticateException|HttpException|ServerException $e) {
            $errors[] = sprintf(
                /* translators: %s: error message */
                __('Authentication failed: %s', 'dpdconnect'),
                $e->getMessage()
            );
        }

        if($jwtToken == null){
            $errors[] = __('No JWT Token received', 'dpdconnect');
        }

        return $errors;
    }

    /**
     * @param string|null $value
     * @param int $maxLength
     * @param int $minLength
     * @return string
     */
    private static function validateLength(
        ?string $value,
        int $maxLength,
        int $minLength = 0
    ): string
    {
        $error = '';

        if(empty($value)) {
            $error = __('Field is empty', 'dpdconnect');
        } else if (mb_strlen($value) > $maxLength){
            $error = sprintf(
                /* translators: %d: maximum number of characters */
                __('Value is too long - max %d characters', 'dpdconnect'),
                $maxLength
            );
        } else if(mb_strlen($value) < $minLength){
            $error = sprintf(
                /* translators: %d: minimum number of characters */
                __('Value is too short - min %d characters', 'dpdconnect'),
                $minLength
            );
        }

        return $error;
    }

    /**
     * @param string|null $value
     * @param string $regex
     * @return string
     */
    private static function validateFormat(
        ?string $value,
        string $regex,
    ): string
    {
        $error = '';

        if (!preg_match($regex, $value)) {
            $error = __('Invalid format', 'dpdconnect');
        }

        return $error;
    }

    /**
     * @param array $validationErrors
     * @return void
     */
    public static function printValidationErrors(array $validationErrors): void
    {
        $cssStyles = 'color: #9e1313; margin: 0;';

        echo '<ul id="error-container">';
        foreach ($validationErrors as $field => $errorMessages){
            foreach ($errorMessages as $error) {
                if(!empty($error)) {
                    // Sanitize field name for display
                    $fieldDisplay = str_replace('_', ' ', $field);
                    $fieldDisplay = ucwords($fieldDisplay);

                    // Escape output to prevent XSS and use proper translation with sprintf
                    echo '<li style="' . esc_attr($cssStyles) . '">';
                    echo esc_html(
                        sprintf(
                            /* translators: %1$s: field name, %2$s: error message */
                            __('%1$s - %2$s', 'dpdconnect'),
                            $fieldDisplay,
                            $error
                        )
                    );
                    echo '</li>';
                }
            }
        }
        echo '</ul>';
    }
}
