<?php

namespace DpdConnect\classes\Handlers;

use ZipArchive;

class Download
{
    public static function pdf($pdfContents, $fileName)
    {
        ob_clean();
        ob_end_flush();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename=' . $fileName . '.pdf');

        echo base64_decode($pdfContents);
        exit;
    }

    public static function zip($response)
    {
        ob_clean();
        ob_end_flush();
        $fileName = 'labels_' . date('YmdHis');
        $zip = new ZipArchive();
        $zipfile = tempnam(sys_get_temp_dir(), "zip");

        $res = $zip->open($zipfile, ZipArchive::OVERWRITE);
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
        exit;
    }

    public static function mergedPdf($response)
    {
        ob_clean();
        ob_end_flush();
        require __DIR__ . '/../../vendor/myokyawhtun/pdfmerger/PDFMerger.php';
        $pdf = new \PDFMerger\PDFMerger();

        $labelResponses = $response->getContent()['labelResponses'];
        foreach ($labelResponses as $item) {
            $path = tempnam(sys_get_temp_dir(), 'dpdpdf');
            $f = fopen($path, 'w');
            fwrite($f, base64_decode($item['label']));

            $pdf->addPDF($path);
        }

        header("Content-Type: application/pdf");
        header('Content-Disposition: attachment; filename="dpd-label-' . date("Ymdhis") . '.pdf"');

        $createdPdf = $pdf->merge('download', 'dpd-label-' . date("Ymdhis") . '.pdf');
        echo $createdPdf;
        die;

    }
}
