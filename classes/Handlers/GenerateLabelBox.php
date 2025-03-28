<?php

namespace DpdConnect\classes\Handlers;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use DpdConnect\classes\Connect\Product;
use DpdConnect\classes\Option;

class GenerateLabelBox
{
    public static function handle()
    {
        add_filter('add_meta_boxes', [self::class, 'add']);
        add_action( 'woocommerce_update_order', [self::class, 'process']);
    }

    public static function add()
    {
        $screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        add_meta_box(
            'meta-box-id',
            __('DPD Connect Generate Labels', 'dpdconnect'),
            [self::class, 'render'],
            $screen,
            'side',
            'high'
        );
    }

    public static function render()
    {
        echo '<p>' . __('Request shipping labels from DPD Connect. Shipments can be seperated in multiple parcels by specifying the amount of needed labels (Only available for supported EU destinations).', 'dpdconnect') . '</p>';
        ?>
        <form action="" method="post">
        <table class="order_actions submitbox">
        <tr class="wide" id="actions">
            <td>
                <input style="width: 100%;" id="DPDlabelAmount" type="number" step="1" min="1" name="parcel_count" value="1"/>
            </td>
            <td>
                <select name="label_type" style="width: 100%;">
                    <option selected disabled>Select an action</option>
                    <option value="dpdconnect_create_labels_bulk_action">Create DPD Labels</option>
                    <?php
                    $product = new Product();
                    foreach ($product->getAllowedProducts() as $dpdProduct) {
                        $label = $dpdProduct['name'];
                        if (strpos(strtolower($dpdProduct['name']), 'dpd') === false) {
                            $label = 'DPD ' . $label;
                        }
                        echo sprintf('<option value="dpdconnect_create_%s_labels_bulk_action">Create %s Labels</option>',
                                $dpdProduct['code'],
                                $label
                            );
                    }
                    ?>
                </select>
            </td>
            <?php
            $volume = Option::defaultPackageType();
            ?>
            <select name="package_type" style="width: 100%;">
                <option selected disabled>Select an parcel type</option>
                <option value="015010010" <?php if(!is_null($volume) && $volume == '015010010') { echo 'selected'; } ?>>Small Parcel</option>
                <option value="100050050" <?php if(!is_null($volume) && $volume == '100050050') { echo 'selected'; } ?>>Normal Parcel</option>
            </select>
        </tr>
            <tr>
                <td colspan="2">
                <input type="submit" value="Generate labels" name="generate_label" style="width: 100%;" class="button"/>
                </td>
            </tr>
        </table>
        </form>
        <?php
    }

    public static function process($orderId)
    {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
       if(isset($_POST['generate_label']) && !empty($_POST['label_type'])) {
            LabelRequest::single($orderId, $_POST['label_type'], $_POST['parcel_count'], $_POST['package_type']);
        }
    }
}
