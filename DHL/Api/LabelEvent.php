<?php

namespace DHL\Api;

class LabelEvent
{
    use Tool;

    protected $api;

    public function __construct()
    {
        $this->api = new Api();
    }

    public function labelInfo($labelInfo = array())
    {
        try{
            $data = $this->api->label($labelInfo);
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit();
        }
        $data = $this->object_to_array($data);
        return $data;
    }


    public function labelPrint($labelInfo = array())
    {
        //get data, data using Base64 encoding
        $data = $this->api->label($labelInfo);
        $pdf = $data->LabelImage->OutputImage;
        $airwayBillNumber = $data->AirwayBillNumber;

        //generate pdf
        $path = __DIR__.'/../pdf/';
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $filename = $path.$airwayBillNumber.'.pdf';
        $fp = fopen($filename, 'w');
        fwrite($fp, base64_decode($pdf));
        fclose($fp);

        //return PDF file path
        return $filename;
    }

    public function labelRequire($labelInfo)
    {
        $data = $this->api->labelRequest($labelInfo);
        $pdf = $data->LabelPrintCommands->LabelPrintCommand;
        $airwayBillNumber = $data->AirwayBillNumber;

        //generate pdf
        $path = __DIR__.'/../pdf/';
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $filename = $path.$airwayBillNumber.'.pdf';
        $fp = fopen($filename, 'w');
        fwrite($fp, base64_decode($pdf));
        fclose($fp);

        //return PDF file path
        return $filename;
    }

}