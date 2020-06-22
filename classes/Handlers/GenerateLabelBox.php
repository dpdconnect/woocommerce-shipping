<?php

namespace DpdConnect\classes\Handlers;

use WC_Order;
use DpdConnect\classes\Handlers\LabelRequest;

class GenerateLabelBox
{
    public static function handle()
    {
        add_filter('add_meta_boxes', [self::class, 'add']);
        add_action( 'save_post', [self::class, 'process']);
    }

    public static function add()
    {
        add_meta_box(
            'meta-box-id',
            __('DPD Connect Generate Labels', 'dpdconnect'),
            [self::class, 'render'],
            'shop_order',
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
                    <option value="dpdconnect_create_predict_labels_bulk_action">Create DPD Predict(Home) Labels</option>
                    <option value="dpdconnect_create_classic_labels_bulk_action">Create DPD Classic Labels</option>
                    <option value="dpdconnect_create_parcelshop_labels_bulk_action">Create DPD ParcelShop Labels</option>
                    <option value="dpdconnect_create_saturday_labels_bulk_action">Create DPD Saturday Labels</option>
                    <option value="dpdconnect_create_express_10_labels_bulk_action">Create DPD Express 10 Labels</option>
                    <option value="dpdconnect_create_express_12_labels_bulk_action">Create DPD Express 12 Labels</option>
                    <option value="dpdconnect_create_express_18_labels_bulk_action">Create DPD Express 18 Labels</option>
                    <option value="dpdconnect_create_return_labels_bulk_action">Create DPD Return Labels</option>
                </select>
            </td>
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

    public static function process($postId)
    {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if(isset($_POST['generate_label'])) {
            LabelRequest::single($postId, $_POST['label_type'], $_POST['parcel_count']);
        }
    }
}
