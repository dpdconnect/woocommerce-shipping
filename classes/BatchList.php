<?php

namespace DpdConnect\classes;

use WP_List_Table;
use DpdConnect\classes\enums\BatchStatus;

class BatchList extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => __('Batch', 'dpdconnect'), //singular name of the listed records
            'plural'   => __('Batches', 'dpdconnect'), //plural name of the listed records
            'ajax'     => false //should this table support ajax?
        ]);
    }

    public static function get_batches($per_page = 5, $page_number = 1)
    {
        global $wpdb;

        $sql = "SELECT * FROM {$wpdb->prefix}dpdconnect_batches";

        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= ! empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        } else {
            $sql .= ' ORDER BY created_at DESC';
        }

        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;
        $result = $wpdb->get_results($sql, 'ARRAY_A');

        return $result;
    }

    public static function record_count()
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}dpdconnect_batches";

        return $wpdb->get_var($sql);
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
            case 'created_at':
            case 'shipment_count':
            case 'success_count':
            case 'failure_count':
            case 'status':
            case 'job_id':
                return $item[$column_name];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    public function column_status($item)
    {
        $status = $item['status'];

        return BatchStatus::tag($status);
    }

    public function get_sortable_columns() {
        $sortable_columns = [
            'id' => ['id', true],
            'created_at' => ['created_at', true],
            'status' => ['status', true]
        ];
        return $sortable_columns;
    }

    public function column_action($item)
    {
        $url = admin_url() .  'admin.php?page=dpdconnect-jobs&batchId=' . $item['id'];
        return '<a href="' . $url . '"><span class="dpdTag">' . __('View jobs') . '</span></a>';
    }

    public function get_columns()
    {
        $columns = [
            'cb' => '<input type="checkbox" />',
            'id' => __('ID', 'dpdconnect'),
            'created_at' => __('Date', 'dpdconnect'),
            'shipment_count' => __('Job count', 'dpdconnect'),
            'success_count' => __('Success count', 'dpdconnect'),
            'failure_count' => __('Failure count', 'dpdconnect'),
            'status' => __('Status', 'dpdconnect'),
            'action' => __('Action', 'dpdconnect'),
        ];

        return $columns;
    }

    public function prepare_items()
    {
        $this->_column_headers = $this->get_column_info();
        //$this->process_bulk_action();
        $per_page = $this->get_items_per_page('batches_per_page', 25);
        $current_page = $this->get_pagenum();
        $total_items = self::record_count();
        $this->set_pagination_args(
            [
                'total_items' => $total_items, //WE have to calculate the total number of items
                'per_page'    => $per_page //WE have to determine how many items to show on a page
            ]
        );
        $this->items = self::get_batches($per_page, $current_page);
    }
}
