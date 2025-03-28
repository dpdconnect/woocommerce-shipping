<?php

namespace DpdConnect\classes\Handlers;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use DpdConnect\classes\Connect\Product;

class SelectDefaultPackageType
{
    public static function handle()
    {
        add_filter('add_meta_boxes', [self::class, 'add']);
    }

    public static function add()
    {
        $screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        add_meta_box(
            'meta-box-id-pt',
            __('DPD Choose PackageType', 'dpdconnect_package_type'),
            [self::class, 'render'],
            $screen,
            'side',
            'high'
        );
    }

    public static function render()
    {
        echo '<p>' . __('Choose Package Type', 'dpdconnect') . '</p>';
        ?>
        <form action="" method="post">
            <table class="order_actions submitbox">
                <tr class="wide" id="actions">
                    <td>
                        <select name="package_type" style="width: 100%;">
                            <option selected disabled>Select an action</option>
                            <option value="dpdconnect_select_package_type">Small Parcel</option>
                            <option value="dpdconnect_select_package_type">Normal Parcel</option>
                            <?php

                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" value="Select" name="choose_package_type" style="width: 100%;" class="button"/>
                    </td>
                </tr>
            </table>
        </form>
        <?php
    }

    public static function process($postId)
    {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if(isset($_POST['generate_label']) && !empty($_POST['label_type'])) {
            LabelRequest::single($postId, $_POST['label_type'], $_POST['parcel_count']);
        }
    }
}
