<?php


namespace DpdConnect\classes\Pages;

use DpdConnect\classes\enums\NoticeType;
use DpdConnect\classes\FreshFreezeHelper;
use DpdConnect\classes\Handlers\LabelRequest;
use DpdConnect\classes\Handlers\Notice;
use DpdConnect\classes\TypeHelper;


class FreshFreeze
{
    public function render()
    {
        if (! isset($_GET['order_ids']) || ! isset($_GET['label_type']) || ! isset($_GET['parcel_count'])) {
            Notice::add(__('An error has occured while gathering Fresh/Freeze data', 'dpdconnect'), NoticeType::ERROR);
            LabelRequest::redirect();
        }

        if (isset($_POST['submit'])) {
            self::process();
        }

        $orders = [];
        foreach ($_GET['order_ids'] as $orderId) {
            $orders[] = wc_get_order($orderId);
        }

        $todayDate = date('Y-m-d');
        $defaultDate = $this->calculateDateAfterWorkdays($todayDate, 5);

        $content = "<style>
            .form-table {
                width: 0;
            }
            .form-table th {
                width: 0 !important;
            }
            .postbox {
                min-width: 0 !important;
                width: 280px !important;
            }
        </style>";
        $content .= "<form action='' method='post'>
            <div class='wrap'>
            <h1>" . __('Enter Fresh/Freeze Expiration Dates', 'dpdconnect') . "</h1>
            <table class='form-table'>
            <tbody>";

        $groupedOrderItems = FreshFreezeHelper::groupOrderItemsByShippingProduct($orders);

        foreach ($orders as $order) {
            $orderItems = [];
            // Collect all fresh and freeze order items in one array for easier handling
            if (isset($groupedOrderItems[$order->get_id()][TypeHelper::DPD_SHIPPING_PRODUCT_FRESH])) {
                $orderItems = array_merge($orderItems, $groupedOrderItems[$order->get_id()][TypeHelper::DPD_SHIPPING_PRODUCT_FRESH]);
            }
            if (isset($groupedOrderItems[$order->get_id()][TypeHelper::DPD_SHIPPING_PRODUCT_FREEZE])) {
                $orderItems = array_merge($orderItems, $groupedOrderItems[$order->get_id()][TypeHelper::DPD_SHIPPING_PRODUCT_FREEZE]);
            }

            $quantities = [];
            foreach ($orderItems as $orderItem) {
                $product = $orderItem->get_product();

                if (! $product) {
                    continue;
                }

                if (isset($quantities[$product->get_sku()])) {
                    $quantities[$product->get_sku()] += 1;
                } else {
                    $quantities[$product->get_sku()] = 1;
                }

            }

            $content .= "<tr class='dpdconnect_row'><th style='padding: 0'><h1>Order {$order->get_id()}</h1></th></tr>";
            $content .= "<tr class='dpdconnect_row' style='width: 100%'>";

            foreach ($orderItems as $index => $orderItem) {
                if ($index % 6 === 0) {
                    $content .= "</tr><tr class='dpdconnect_row' style='width: 100%'>";
                }

                /** @var \WC_Product $product */
                $product = $orderItem->get_product();
                $shippingProduct = wc_get_order($product->get_id())->get_meta('dpd_shipping_product');
                $weight = !empty($product->get_weight()) ?: 0;
                $weightUnit = get_option('woocommerce_weight_unit');

                $content .= "<th scope='row' class='postbox' style='padding-top: 0;'>
                                <div class='inside' style='font-size: 14px'>
                                    <div style='float: left;'>
                                        {$product->get_image([150, 150])}
                                    </div>
                                    <div style='float: right;'>
                                        <p style='font-size: 16px; color: #21759b; margin-top: 3px'>{$product->get_title()}</p>
                                        SKU: <span style='font-weight: normal'>{$product->get_sku()}</span>
                                        Type: <span style='font-weight: normal'>$shippingProduct</span><br>
                                        Weight: <span style='font-weight: normal'>$weight $weightUnit</span><br>
                                        Quantity: <span style='font-weight: normal'>{$quantities[$product->get_sku()]}</span><br>
                                        <br>
                                        <label>" . __('Expiration date', 'dpdconnect') . "</label><br>
                                        <input type='date' name='date_{$order->get_id()}_{$shippingProduct}_{$product->get_id()}' value='$defaultDate' min='$todayDate'/>
                                    </div>
                                </div>
                            </th>";
            }
            $content .= "</tr>";
            $content .= "</div>";
        }

        $content .= "</tbody>
            </table>
            <p class='submit'>
                <input type='submit' name='submit' id='submit' class='button button-primary' value='Submit'>
            </p>
            </div>
            </form>";

        echo $content;
    }

    public static function process()
    {
        // Get all items where key begins with 'data_'
        $items = array_filter($_POST, function($key) {
            return substr($key, 0, strlen('date_')) === 'date_';
        }, ARRAY_FILTER_USE_KEY);

        $freshFreezeData = [];
        // Key looks like this: date_{orderId}_{shippingProduct}_{orderItemId}
        foreach ($items as $key => $freshFreezeDate) {
            $orderId = explode('_', $key)[1];
            $shippingProduct = explode('_', $key)[2];
            $orderItemId = explode('_', $key)[3];

            $freshFreezeData[$orderId][$shippingProduct][$orderItemId] = date("Ymd", strtotime($freshFreezeDate));
        }


        if(isset($_GET['order_ids']) && count($_GET['order_ids']) === 1) {
            LabelRequest::single($_GET['order_ids'][0], $_GET['label_type'], $_GET['parcel_count'], $freshFreezeData);
        } else {
            LabelRequest::bulk(null, $_GET['label_type'], $_GET['order_ids'], $freshFreezeData);
        }
    }

    // Calculate next date after x working days. Recursively woohoo
    private function calculateDateAfterWorkdays(string $date, int $workingDays)
    {
        if ($workingDays <= 0 && date('N', strtotime($date)) < 6) {
            return $date;
        }

        // Day is weekend
        if (date('N', strtotime($date)) >= 6) {
            return $this->calculateDateAfterWorkdays(date('Y-m-d', strtotime($date . ' + 1 day')), $workingDays);
        }

        return $this->calculateDateAfterWorkdays(date('Y-m-d', strtotime($date . ' + 1 day')), --$workingDays);
    }
}
