<?php

namespace DpdConnect\classes\Connect;

class Country extends Connection
{
    public function getList()
    {
        return $this->client->getCountries()->getList();
    }

    public function isPartOfSingleMarket($iso2)
    {
        if ($key = $this->lookupCountry($iso2)) {
            return $this->getList()[$key]['singleMarket'];
        }

        return false;
    }

    private function lookupCountry($iso2)
    {
        return @array_search(strtoupper($iso2), array_column($this->getList(), 'country'), true);
    }
}
