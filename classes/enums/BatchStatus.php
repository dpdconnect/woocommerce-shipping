<?php

namespace DpdConnect\classes\enums;

class BatchStatus
{
    const UPDATEPROPS = [
        'started', 'nonce', 'shipmentCount', 'successCount', 'failureCount',
    ];
    const STATUSREQUEST = 'status_request';
    const STATUSQUEUED = 'status_queued';
    const STATUSPROCESSING = 'status_processing';
    const STATUSSUCCESS = 'status_success';
    const STATUSFAILED = 'status_failed';
    const STATUSPARTIALLYFAILED = 'status_partially_failed';

    public static function tag($status)
    {
        switch ($status) {
            case self::STATUSREQUEST:
                return "<span class='dpdTag request'>" . __('Request', 'dpdconnect') . "</span>";
            case self::STATUSQUEUED:
                return "<span class='dpdTag queued'>" . __('Queued', 'dpdconnect') . "</span>";
            case self::STATUSPROCESSING:
                return "<span class='dpdTag processing'>" . __('Processing', 'dpdconnect') . "</span>";
            case self::STATUSSUCCESS:
                return "<span class='dpdTag success'>" . __('Success', 'dpdconnect') . "</span>";
            case self::STATUSFAILED:
                return "<span class='dpdTag failed'>" . __('Failed', 'dpdconnect') . "</span>";
            case self::STATUSPARTIALLYFAILED:
                return "<span class='dpdTag failed'>" . __('Partially failed', 'dpdconnect') . "</span>";
            default:
                return;
        }
    }
}
