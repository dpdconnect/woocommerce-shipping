<?php

namespace DpdConnect\classes;

use DpdConnect\classes\Database\Label;
use DpdConnect\classes\Handlers\Download;
use DpdConnect\classes\Handlers\LabelRequest;

class Router
{
    public static function init($parameters)
    {
        if (!isset($parameters['plugin'])) {
            return;
        }

        if (!$parameters['plugin'] === 'dpdconnect') {
            return;
        }

        if (isset($parameters['file']) && $parameters['file'] === 'shipping_label') {
            $labelRepo = new Label();
            $label = $labelRepo->get($parameters['id']);

            return Download::pdf($label['contents'], $label['shipment_identifier']);
        }

        if (isset($parameters['page']) && $parameters['page'] === 'dpdconnect_print_shipment_label') {
            add_action('woocommerce_after_register_post_type', [LabelRequest::class, 'single']);
        }
    }
}
