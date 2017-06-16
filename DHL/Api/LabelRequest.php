<?php

namespace DHL\Api;

class LabelRequest
{
    protected $siteID;
    protected $password;
    protected $messageReference;
    protected $config;

    public function __construct()
    {
        $this->siteID = config('dhl.SiteID');
        $this->password = config('dhl.Password');
        $this->messageReference = config('dhl.MessageReference');
    }

    public function handle($data, $info)
    {
        //解析XML
        $data = simplexml_load_string($data);

        //头部信息
        $data->Request->ServiceHeader->MessageTime = date('Y-m-d').'T'.date("H:i:s").'.000+08:00';//Message time
        $data->Request->ServiceHeader->SiteID = $this->siteID;
        $data->Request->ServiceHeader->Password = $this->password;
        $data->Request->ServiceHeader->MessageReference = $this->messageReference;
        $data->AirwayBillNumber = $info['tracking_number'];
        $data->Origin->PostCode = config('dhl.ShipperPostalCode');
        $data->Origin->City = config('dhl.ShipperCity');
        $data->Origin->CountryCode = config('dhl.ShipperCountryCode');
        $data->Origin->CountryName = config('dhl.ShipperCountryName');
        $data->Origin->SvcAreaCode = config('dhl.ShipperCountryCode');
        $data->Origin->AddrLine1 = config('dhl.ShipperCompanyName');
        $data->Origin->AddrLine2 = config('dhl.ShipperPersonName');
        $data->Origin->AddrLine3 = config('dhl.ShipperAddressLine');
        $data->Origin->AddrLine4 = config('dhl.ShipperPostalCode').' '.config('dhl.ShipperCity');
        $data->Origin->AddrLine5 = config('dhl.ShipperCountryName');
        $data->Origin->PhoneNum = config('dhl.ShipperPhoneNumber');
        $data->Destination->PostCode = $info['zip_code'];
        $data->Destination->City = $info['city'];
        $data->Destination->CountryCode = $info['country'];
        $data->Destination->CountryName = $info['country'];
        $data->Destination->AddrLine1 = $info['name'];
        $data->Destination->AddrLine2 = $info['name'];
        $data->Destination->AddrLine3 = $info['address'];
        $data->Destination->AddrLine4 = $info['zip_code'].' '.$info['state'];
        $data->Destination->AddrLine5 = $info['country'];
        $data->Destination->ContactName = $info['phone_number'];
        $data->Shipment->GlobalProductCode = 'P';
        $data->Shipment->ShptCalendarDate = date('Y-m-d');
        $data->Shipment->WeightUOM = 'Kg';
        $data->Shipment->Weight = $info['weight']/1000;
        $data->Shipment->PickupDate = date('Y-m-d');
        $data->Shipment->TotalNumOfPcs = 2;
        $data->Pieces->Piece->LicencePlateNum = 'JD014600004438968000';

        //Clear blank setting
        $result = $data->saveXML();
        $result = preg_replace('/\<[a-zA-Z]*\/\>/', '', $result);
        $result = preg_replace('/<[a-zA-Z]*\>\<\/[a-zA-Z]*\>/', '', $result);

        //return xml
        return $result;
    }
}