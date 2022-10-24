<?php

namespace DpdConnect\classes\Database;

use DpdConnect\classes\enums\JobStatus;
use DpdConnect\classes\enums\BatchStatus;

class Batch
{
    private $db;

    private $job;

    private $table;

    public function __construct()
    {
        global $wpdb;

        $this->db = $wpdb;
        $this->job = new job();
        $this->table = $this->db->prefix . 'dpdconnect_batches';
    }

    public function getTable()
    {
        return $this->table;
    }

    public function create($shipments)
    {
        $data = [
            'status' => BatchStatus::STATUSQUEUED,
            'created_at' => date('Y-m-d H:i:s'),
            'shipment_count' => count($shipments),
            'success_count' => null,
            'failure_count' => null,
        ];

        $this->db->insert($this->table, $data);
        return $this->db->insert_id;
    }

    public function updateStatus($job)
    {
        $batch = $this->getByJobId($job['id']);
        $count = $this->countSuccessAndFailures($job['batch_id']);
        $batchStatus = $this->parseStatus($batch, $count);

        /**
         * Update the batch with new counts
         */
        $data = [
            'success_count' => $count['success'],
            'failure_count' => $count['failed'],
            'status' => $batchStatus,
        ];
        $where = [
            'id' => $job['batch_id']
        ];
        $result = $this->db->update($this->table, $data, $where);
    }

    private function getByJobId($jobId)
    {
        $sql = $this->db->prepare(
            "SELECT *
               FROM $this->table 
              WHERE id
                 IN (SELECT batch_id
                       FROM {$this->job->getTable()}
                      WHERE id = %s)",
            $jobId
        );

        return $this->db->get_results($sql, 'ARRAY_A')[0];
    }

    private function countSuccessAndFailures($batchId)
    {
        $sql = $this->db->prepare(
            "SELECT SUM(IF (status = %s, 1, 0)) AS success,
                SUM(IF (status = %s, 1, 0)) AS failed
               FROM {$this->job->getTable()}
              WHERE batch_id = %d",
            [
                JobStatus::STATUSSUCCESS,
                JobStatus::STATUSFAILED,
                $batchId,
            ]
        );
        return $this->db->get_results($sql, 'ARRAY_A')[0];
    }

    private function parseStatus($batch, $count)
    {
        if ($batch['shipment_count'] > ($count['failed'] + $count['success'])) {
            return BatchStatus::STATUSPROCESSING;
        }

        if ($batch['shipment_count'] === $count['success']) {
            return BatchStatus::STATUSSUCCESS;
        }

        if ($batch['shipment_count'] === $count['failed']) {
            return BatchStatus::STATUSFAILED;
        }

        if ($batch['shipment_count'] > $count['failed']) {
            return BatchStatus::STATUSPARTIALLYFAILED;
        }
    }
}
