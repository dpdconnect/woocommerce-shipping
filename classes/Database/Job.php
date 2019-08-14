<?php

namespace DpdConnect\classes\Database;

use DpdConnect\classes\enums\BatchStatus;
use DpdConnect\classes\enums\JobStatus;

class Job
{
    private $db;

    private $table;

    public function __construct()
    {
        global $wpdb;

        $this->db = $wpdb;
        $this->table = $this->db->prefix . 'dpdconnect_jobs';
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getByExternalId($externalId)
    {
        $sql = $this->db->prepare(
            "SELECT *
               FROM $this->table
              WHERE external_id = %s",
            $externalId
        );

        return $this->db->get_results($sql, 'ARRAY_A')[0];
    }

    public function create($batchId, $externalId, $orderId, $type)
    {
        $data = [
            'created_at' => date('Y-m-d H:i:s'),
            'external_id' => $externalId,
            'batch_id' => $batchId,
            'order_id' => $orderId,
            'status' => JobStatus::STATUSQUEUED,
            'type' => $type,
        ];

        $result = $this->db->insert($this->table, $data);
    }

    public function getByOrderId($orderId, $type)
    {
        $sql = $this->db->prepare(
            "SELECT *
               FROM $this->table
              WHERE order_id = %s
                AND type = %d
          ORDER BY created_at DESC
              LIMIT 1",
            $orderId,
            $type
        );

        if (isset($this->db->get_results($sql, 'ARRAY_A')[0])) {
            return $this->db->get_results($sql, 'ARRAY_A')[0];
        }
    }

    public function updateStatus($job, $status, $stateMessage = null, $errors = null, $labelId = null)
    {
        $data = [
            'status' => $status,
            'state_message' => $stateMessage,
        ];

        if ($errors) {
            $data['error'] = serialize($errors);
        }

        if ($labelId) {
            $data['label_id'] = $labelId;
        }

        $where = [
            'id' => $job['id'],
        ];

        $result = $this->db->update($this->table, $data, $where);
    }
}
