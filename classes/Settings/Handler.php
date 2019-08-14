<?php

namespace DpdConnect\classes\Settings;

use DpdConnect\classes\Settings\Menu;
use DpdConnect\classes\Settings\General;
use DpdConnect\classes\Settings\Credentials;
use DpdConnect\classes\Settings\Company;
use DpdConnect\classes\Settings\Product;
use DpdConnect\classes\Settings\Parcelshop;
use DpdConnect\classes\Settings\Advanced;

class Handler
{
    public static function handle()
    {
        Menu::handle();
        General::handle();
        Credentials::handle();
        Company::handle();
        Product::handle();
        Parcelshop::handle();
        Advanced::handle();
    }
}
