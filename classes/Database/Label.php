<?php

namespace DpdConnect\classes\Database;

class Label
{
    private $db;

    public function __construct()
    {
        global $wpdb;

        $this->db = $wpdb;
        $this->table = $this->db->prefix . 'dpdconnect_labels';
    }

    public function create($orderId, $contents, $type, $shipmentIdentifier, $parcelNumbers)
    {
        $data = [
            'order_id' => $orderId,
            'contents' => $contents,
            'type' => $type,
            'shipment_identifier' => $shipmentIdentifier,
            'parcel_numbers' => $parcelNumbers,
        ];
        $result = $this->db->insert($this->table, $data);
        return $this->db->insert_id;
    }

    public function get($id)
    {
        $sql = $this->db->prepare(
            "SELECT *
               FROM $this->table
              WHERE id = %s
              LIMIT 1",
            $id
        );

        if (isset($this->db->get_results($sql, 'ARRAY_A')[0])) {
            return $this->db->get_results($sql, 'ARRAY_A')[0];
        }
    }

    public function getByOrderId($orderId, $type)
    {
        $sql = $this->db->prepare(
            "SELECT *
               FROM $this->table
              WHERE order_id = %s
                AND type = %s
           ORDER BY created_at DESC
              LIMIT 1",
            $orderId,
            $type
        );

        if (isset($this->db->get_results($sql, 'ARRAY_A')[0])) {
            return $this->db->get_results($sql, 'ARRAY_A')[0];
        }
    }
}
