<?php

namespace DpdConnect\classes\Handlers;

use ZipArchive;

class Download
{
    public static function pdf($pdfContents, $fileName)
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename=' . $fileName . '.pdf');

        echo base64_decode($pdfContents);
        exit;
    }

    public static function zip($response)
    {
        $fileName = 'labels_' . date('YmdHis');
        $zip = new ZipArchive();
        $zipfile = tempnam(sys_get_temp_dir(), "zip");
        $res = $zip->open($zipfile, ZipArchive::CREATE);
        $labelResponses = $response->getContent()['labelResponses'];

        foreach ($labelResponses as $labelResponse) {
            $pdf = base64_decode($labelResponse['label']);
            $fileName = $labelResponse['shipmentIdentifier'] . '.pdf';
            $zip->addFromString($fileName, $pdf);
        }

        $zip->close();
        header("Content-Type: application/zip");
        header('Content-Disposition: attachment; filename="dpd-label-' . date("Ymdhis") . '.zip"');

        echo file_get_contents($zipfile);
        unlink($zipfile);
    }
}
