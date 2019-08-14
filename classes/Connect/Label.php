<?php

namespace DpdConnect\classes\Connect;

class Label extends Connection
{
    public function get($parcelNumber)
    {
        $response = $this->client->getParcel()->getLabel($parcelNumber);

        $label = base64_encode($response);
        return $label;
    }
}
