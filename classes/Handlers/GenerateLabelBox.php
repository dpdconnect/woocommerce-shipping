<?php

namespace DpdConnect\classes\Handlers;

use WC_Order;

class GenerateLabelBox
{
    public static function handle()
    {
        add_filter('add_meta_boxes', [self::class, 'add']);
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
        global $post;

        $current_order = new WC_Order($post->ID);
        $shipping_method = $current_order->get_items('shipping');
        foreach ($shipping_method as $el) {
            $shipping_id = $el['method_id'];
        }
        $labelPrintUrlArray = [
            'plugin'            => 'dpdconnect',
            'page'              => 'dpdconnect_print_shipment_label',
            'order_id'          => $current_order->get_id(),
            'redirect_to'       => base64_encode($_SERVER['REQUEST_URI'] . '?' . $_SERVER['QUERY_STRING']),
        ];
        // Print label regarding the shipping method
        $printLabelUrl = add_query_arg($labelPrintUrlArray, admin_url());
        // Print Returnlabel regarding the shipping method
        $labelPrintUrlArray['returnlabel'] = 1;
        $printReturnLabelUrl = add_query_arg($labelPrintUrlArray, admin_url());

        echo '<p>' . __('Request shipping labels from DPD Connect. Shipments can be seperated in multiple parcels by specifying the amount of needed labels (Only available for supported EU destinations).', 'dpdconnect') . '</p>';
        ?>
        <table>
            <tr class="wide">
                <td><input id="DPDlabelAmount" type="number" step="1" min="1" name="DPDlabelAmount" value="1" style="width: 100%"/></td>
                <td><a id="printLabelUrlHref" href="<?=$printLabelUrl?>" target="_blank" class="button save_order button-primary button_dpdconnect_full" name="save" value="Update"><?php echo __('Shipping', 'dpdconnect')?></a></td>
            </tr>
            <tr class="wide">
                <td><input id="DPDReturnlabelAmount" type="number" step="1" min="1" name="DPDReturnlabelAmount" value="1" style="width: 100%"/></td>
                <td><a id="printReturnLabelUrlHref" href="<?=$printReturnLabelUrl?>" target="_blank" class="button save_order button-primary button_dpdconnect_full" name="save" value="Update"><?php echo __('Return', 'dpdconnect') ?></a></td>
            </tr>
        </table>

        <script>
            var printLabelUrl = "<?=$printLabelUrl?>";
            var printReturnLabelUrl = "<?=$printReturnLabelUrl?>";

            jQuery("#DPDlabelAmount").change(function() {
                var amount = jQuery("#DPDlabelAmount").val();
                jQuery("#printLabelUrlHref").attr("href", printLabelUrl + "&DPDlabelAmount=" + amount);
            });

            jQuery("#DPDReturnlabelAmount").change(function() {
                var amount = jQuery("#DPDReturnlabelAmount").val();
                jQuery("#printReturnLabelUrlHref").attr("href", printReturnLabelUrl + "&DPDlabelAmount=" + amount);
            });

            jQuery( document ).ready(function() {
                // Set URL to a minimum of 1
                jQuery("#printLabelUrlHref").attr("href", printLabelUrl + "&DPDlabelAmount=1");
                jQuery("#printReturnLabelUrlHref").attr("href", printReturnLabelUrl + "&DPDlabelAmount=1");
            });
        </script>
        <?php
    }
}
