<?php

namespace DpdConnect\classes;

use WP_List_Table;
use DpdConnect\classes\enums\JobStatus;
use DpdConnect\classes\enums\ParcelType;

class JobList extends WP_List_Table
{
    private $batchId;

    public function __construct()
    {
        parent::__construct([
            'singular' => __('Job', 'dpdconnect'),
            'plural'   => __('Jobs', 'dpdconnect'),
            'ajax'     => false
        ]);

        if (isset($_GET['batchId'])) {
            $this->batchId = $_GET['batchId'];
        }
    }

    public static function get_batches($per_page = 5, $page_number = 1, $batchId = null)
    {
        global $wpdb;

        $sql = "SELECT * FROM {$wpdb->prefix}dpdconnect_jobs";

        if (!is_null($batchId)) {
            $sql .= " WHERE batch_id = {$batchId}";
        }

        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= ! empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }

        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;
        $result = $wpdb->get_results($sql, 'ARRAY_A');

        return $result;
    }

    public static function record_count($batchId)
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}dpdconnect_jobs";

        if (!is_null($batchId)) {
            $sql .= ' WHERE batch_id = ' . $batchId;
        }

        return $wpdb->get_var($sql);
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'created_at':
            case 'batch_id':
            case 'order_id':
            case 'external_id':
                return $item[$column_name];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    public function column_status($item)
    {
        $status = $item['status'];

        return JobStatus::tag($status);
    }

    public function column_error($item)
    {
        $errorData = $item['error'];
        if (empty($errorData)) {
            return null;
        }

        $unserialized = unserialize($errorData);
        if (isset($unserialized['_embedded']) && isset($unserialized['_embedded']['errors'])) {
            $errorCount = count($unserialized['_embedded']['errors']);
            $firstError = $unserialized['_embedded']['errors'][0]['message'];
            if ($errorCount === 1) {
                return $firstError;
            }
            return $firstError . ' ' . sprintf(__('And %s more errors.', 'dpdconnect'), $errorCount - 1);
        }
    }

    public function column_state_message($item)
    {
        return $item['state_message'];
    }

    public function get_sortable_columns()
    {
        $sortable_columns = [
            'batch_id' => ['batch_id', true],
            'created_at' => ['created_at', true],
            'status' => ['status', true],
            'order_id' => ['order_id', true]
        ];
        return $sortable_columns;
    }

    public function get_columns()
    {
        $columns = [
            'cb' => '<input type="checkbox" />',
            'created_at' => __('Date', 'dpdconnect'),
            'batch_id' => __('Batch ID', 'dpdconnect'),
            'order_id' => __('Order ID', 'dpdconnect'),
            'status' => __('Status', 'dpdconnect'),
            'type' => __('Type', 'dpdconnect'),
            'external_id' => __('Job ID', 'dpdconnect'),
            'error' => __('Error', 'dpdconnect'),
            'state_message' => __('Message', 'dpdconnect'),
            'action' => __('Action', 'dpdconnect'),
        ];

        return $columns;
    }

    public function prepare_items()
    {
        $this->_column_headers = $this->get_column_info();
        $per_page = $this->get_items_per_page('jobs_per_page', 25);
        $current_page = $this->get_pagenum();
        $total_items = self::record_count($this->batchId);
        $this->set_pagination_args(
            [
                'total_items' => $total_items, //WE have to calculate the total number of items
                'per_page'    => $per_page //WE have to determine how many items to show on a page
            ]
        );
        $this->items = self::get_batches($per_page, $current_page, $this->batchId);
    }

    public function column_type($item)
    {
        if ((int) $item['type'] === ParcelType::TYPERETURN) {
            return __('Return label');
        }

        return __('Shipping label');
    }

    public function column_action($item)
    {
        $jobUrl = admin_url() .  'admin.php?page=dpdconnect-job-details&jobId=' . $item['id'];
        $jobButton = '<a href="' . $jobUrl . '"><span class="dpdTag">' . __('View details', 'dpdconnect') . '</span></a>';

        if ($item['label_id']) {
            $shippingId = $item['label_id'];
            $labelUrl = add_query_arg(
                [
                    'plugin' => 'dpdconnect',
                    'file' => 'shipping_label',
                    'id' => $item['label_id'],
                ],
                admin_url()
            );
            $labelButton = '<a href="' . $labelUrl . '" title="' . __('Download PDF Label', 'dpdconnect') . '"><span class="dpdTag">' . __('PDF', 'dpdconnect') . '</span></a>';
            return $labelButton . ' ' . $jobButton;
        }
        return $jobButton;
    }
}
