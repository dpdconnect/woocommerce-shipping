<?php

namespace DpdConnect\classes;

use DpdConnect\classes\enums\JobStatus;
use DpdConnect\classes\enums\ParcelType;

class JobView
{
    private $db;

    public function __construct()
    {
        global $wpdb;

        $this->db = $wpdb;
    }

    public function render()
    {
        echo "<h1>" . __('Job details', 'dpdconnect') . "</h1>";

        $job = $this->get_job();

        echo "
            <table>
                <tr>
                    <td>External ID</td>
                    <td>{$job['external_id']}</td>
                </tr>
                <tr>
                    <td>Date</td>
                    <td>{$job['created_at']}</td>
                </tr>
                <tr>
                    <td>Order ID</td>
                    <td>{$job['order_id']}</td>
                </tr>
                <tr>
                    <td>Batch ID</td>
                    <td>{$job['batch_id']}</td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td>{$this->status($job)}</td>
                </tr>
                <tr>
                    <td>Type</td>
                    <td>{$this->parse_type($job['type'])}</td>
                </tr>
                <tr>
                    <td>StateMessage</td>
                    <td>{$job['state_message']}</td>
                </tr>
                <tr>
                    <td>Download</td>
                    <td>{$this->download_button($job)}</td>
                </tr>
                <tr>
                    <td>Error</td>
                    <td>{$this->pretty_error($job['error'])}</td>
                </tr>
            </table>
        ";
    }

    private function get_job()
    {
        if (!isset($_GET['jobId'])) {
            exit('Job id missing');
        }

        $jobId = (int) $_GET['jobId'];

        $table = $this->db->prefix . 'dpdconnect_jobs';

        $sql = $this->db->prepare(
            "SELECT *
               FROM $table
              WHERE id = %s",
            $jobId
        );

        return $this->db->get_results($sql, 'ARRAY_A')[0];
    }

    private function pretty_error($error)
    {
        return '<pre>' . print_r(unserialize($error), true) . '</pre>';
    }

    private function parse_type($type)
    {
        switch ($type) {
            case ParcelType::TYPEREGULAR:
                return __('Shipping label');
            case ParcelType::TYPERETURN:
                return __('Return label');
            default:
                return __('Unknown label type');
        }
    }

    private function download_button($item)
    {
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
            return '<a href="' . $labelUrl . '" title="' . __('Download PDF Label', 'dpdconnect') . '"><span class="dpdTag">' . __('PDF', 'dpdconnect') . '</span></a>';
        }
    }

    private function status($item)
    {
        $status = $item['status'];

        return JobStatus::tag($status);
    }
}
