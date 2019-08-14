<?php

namespace DpdConnect\classes\Handlers;

class Translation
{
    public static function handle()
    {
        load_plugin_textdomain('dpdconnect', false, basename(dirname(dirname(dirname(__FILE__)))) . '/languages/');
    }
}
