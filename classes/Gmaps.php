<?php

namespace DpdConnect\classes;

use DpdConnect\classes\Option;

class Gmaps
{
    public static function getGeoData($postal_code, $isoCode)
    {
        $gmapsKey = Option::googleMapsApiServerKey();
        $data = urlencode('country:' . $isoCode . '|postal_code:' . $postal_code);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?key=" . $gmapsKey . "&components=" . $data;
        $ch = curl_init();
        $timeout = 20;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    			'accept-language: nl-NL,nl;q=0.9,en-US;q=0.8,en;q=0.7'
			));

        $source = curl_exec($ch);
        curl_close($ch);
        $gmapsData = json_decode($source);

        if (count($gmapsData->results) > 0) {
            $latitude = $gmapsData->results[0]->geometry->location->lat;
            $longitude = $gmapsData->results[0]->geometry->location->lng;

            return [
                'longitude' => $longitude,
                'latitude' => $latitude,
            ];
        }

        return;
    }
}
