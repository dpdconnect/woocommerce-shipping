<?php

namespace DpdConnect\classes\Handlers;

class Notice
{
    public static function handle()
    {
        add_action('admin_notices', [self::class, 'flash'], 12);
    }

    public static function add($notice = "", $type = "warning", $dismissible = true)
    {
        $notices = get_option("my_flash_notices", []);
        $dismissible_text = ( $dismissible ) ? "is-dismissible" : "";
        $notices[] = [
            "notice" => $notice,
            "type" => $type,
            "dismissible" => $dismissible_text
        ];

        update_option("my_flash_notices", $notices);
    }

    public static function flash()
    {
        $notices = get_option("my_flash_notices", []);

        foreach ($notices as $notice) {
            printf(
                '<div class="notice notice-%1$s %2$s"><p>DPD Connect: %3$s</p></div>',
                $notice['type'],
                $notice['dismissible'],
                $notice['notice']
            );
        }

        if (! empty($notices)) {
            delete_option("my_flash_notices", []);
        }
    }
}
