<?php

namespace DHL\Api;

class DataHandle
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

    public function tracking($data, $tracking_number)
    {
        //Retrieves the SimpleXMLElement object from the XML string.
        $simple = simplexml_load_string($data);

        //setting parameter
        $simple->AWBNumber = $tracking_number;
        $simple->Request->ServiceHeader->MessageTime = date('Y-m-d').'T'.date("H:i:s");//Message time
        $simple->Request->ServiceHeader->SiteID = $this->siteID ? : response('siteID is null!', 403);
        $simple->Request->ServiceHeader->Password = $this->password ? : response('password is null!', 403);
        $simple->Request->ServiceHeader->MessageReference = $this->messageReference ? : response('messageReference is null!', 403);
        $simple->LanguageCode = 'en';
        $simple->LevelOfDetails = 'ALL_CHECK_POINTS';
        $simple->PiecesEnabled = 'S';
        $simple->CountryCode = 'CN';
        return $simple;
    }

    public function lable($data, $labelInfo)
    {
        //AddressLine
        $data = $this->addressLine($data, $labelInfo);
        //exportDeclaration
        $data = $this->exportDeclaration($data, $labelInfo);
        //Reference
        $data = $this->reference($data, $labelInfo);
        //Pieces
        $data = $this->pieces($data, $labelInfo);

        //Set parameters
        $data = simplexml_load_string($data);
        $data->Request->ServiceHeader->MessageTime = date('Y-m-d').'T'.date("H:i:s");//Message time
        $data->Request->ServiceHeader->SiteID = $this->siteID;
        $data->Request->ServiceHeader->Password = $this->password;
        $data->Request->ServiceHeader->MessageReference = $this->messageReference;
        $data->RegionCode = $labelInfo['RegionCode'];
        $data->LanguageCode = @$labelInfo['LanguageCode'] ? : 'en';
        $data->PiecesEnabled = @$labelInfo['PiecesEnabled'] ? : 'Y';
        $data->Billing->ShipperAccountNumber = config('dhl.ShipperID');
        $data->Billing->ShippingPaymentType = $labelInfo['Billing']['ShippingPaymentType'];
        $data->Consignee->CompanyName = $labelInfo['Consignee']['CompanyName'];
        $data->Consignee->City = $labelInfo['Consignee']['City'];
        $data->Consignee->DivisionCode = @$labelInfo['Consignee']['DivisionCode'] ? : null;
        $data->Consignee->PostalCode = @$labelInfo['Consignee']['PostalCode'] ? : null;
        $data->Consignee->CountryCode = $labelInfo['Consignee']['CountryCode'];
        $data->Consignee->CountryName = $labelInfo['Consignee']['CountryName'];
        $data->Consignee->Contact->PersonName = $labelInfo['Consignee']['Contact']['PersonName'];
        $data->Consignee->Contact->PhoneNumber = $labelInfo['Consignee']['Contact']['PhoneNumber'];
        $data->Consignee->Contact->Email = @$labelInfo['Consignee']['Contact']['Email'] ? : null;
        $data->Consignee->Contact->MobilePhoneNumber = @$labelInfo['Consignee']['Contact']['MobilePhoneNumber'] ? : null;
        $data->Dutiable->DeclaredValue = $labelInfo['Dutiable']['DeclaredValue'];
        $data->Dutiable->DeclaredCurrency = $labelInfo['Dutiable']['DeclaredCurrency'];
        $data->Dutiable->ShipperEIN = @$labelInfo['Dutiable']['ShipperEIN'] ? : null;
        $data->ShipmentDetails->NumberOfPieces = $labelInfo['ShipmentDetails']['NumberOfPieces'] ? : count($labelInfo['Pieces']);
        $data->ShipmentDetails->Weight = $labelInfo['ShipmentDetails']['Weight'];
        $data->ShipmentDetails->WeightUnit = $labelInfo['ShipmentDetails']['WeightUnit'];
        $data->ShipmentDetails->GlobalProductCode = $labelInfo['ShipmentDetails']['GlobalProductCode'];
        $data->ShipmentDetails->LocalProductCode = $labelInfo['ShipmentDetails']['LocalProductCode'];
        $data->ShipmentDetails->Date = @$labelInfo['ShipmentDetails']['Date'] ? : date('Y-m-d');
        $data->ShipmentDetails->Contents = $labelInfo['ShipmentDetails']['Contents'];
        $data->ShipmentDetails->DimensionUnit = $labelInfo['ShipmentDetails']['DimensionUnit'];
        $data->ShipmentDetails->CurrencyCode = $labelInfo['ShipmentDetails']['CurrencyCode'];
        $data->Shipper->ShipperID = config('dhl.ShipperID');
        $data->Shipper->CompanyName = config('dhl.ShipperCompanyName');
        $data->Shipper->AddressLine = config('dhl.ShipperAddressLine');
        $data->Shipper->City = config('dhl.ShipperCity');
        $data->Shipper->PostalCode = config('dhl.ShipperPostalCode');
        $data->Shipper->CountryCode = config('dhl.ShipperCountryCode');
        $data->Shipper->CountryName = config('dhl.ShipperCountryName');
        $data->Shipper->Contact->PersonName = config('dhl.ShipperPersonName');
        $data->Shipper->Contact->PhoneNumber = config('dhl.ShipperPhoneNumber');
        $data->Shipper->Contact->Email = config('dhl.ShipperEmail');

        //Clear blank setting
        $result = $data->saveXML();
        $result = preg_replace('/\<[a-zA-Z]*\/\>/', '', $result);
        $result = preg_replace('/<[a-zA-Z]*\>\<\/[a-zA-Z]*\>/', '', $result);

        //return xml
        return $result;
    }

    public function addressLine($data, $labelInfo)
    {
        //must be an array
        $address_arr = $labelInfo['Consignee']['AddressLine'] ? : null;
        $count_address_arr = count($address_arr);
        if ($count_address_arr == 0 || !is_array($address_arr)) {
            response('AddressLine is null or not a array!', 403);
        } else if ($count_address_arr > 3) {
            response('AddressLine less than or equal to 3!', 403);
        }

        //generate xml
        $address = null;
        foreach ($address_arr as $item) {
            $address .= "<AddressLine>$item</AddressLine>\n";
        }

        //replace data
        return str_replace('<AddressLine></AddressLine>', $address, $data);
    }

    public function exportDeclaration($data, $labelInfo)
    {
        //must be an array
        $exportDeclaration_arr = @$labelInfo['ExportDeclaration'] ? : null;
        $exportDeclaration = null;
        if (!empty($exportDeclaration_arr)) {
            $exportDeclaration =
                "<InterConsignee>".$exportDeclaration['InterConsignee']."</InterConsignee>\n".
                "<IsPartiesRelation>".$exportDeclaration['IsPartiesRelation']."</IsPartiesRelation>\n".
                "<ECCN>".$exportDeclaration['ECCN']."</ECCN>\n".
                "<SignatureName>".$exportDeclaration['SignatureName']."</SignatureName>\n".
                "<SignatureTitle>".$exportDeclaration['SignatureTitle']."</SignatureTitle>\n".
                "<ExportReason>".$exportDeclaration['ExportReason']."</ExportReason>\n".
                "<ExportReasonCode>".$exportDeclaration['ExportReasonCode']."</ExportReasonCode>\n";

            $exportLineItem_arr = $exportDeclaration['ExportLineItem'] ? : null;
            if (empty($exportLineItem_arr) || !is_array($exportLineItem_arr)) {
                response('AddressLine is null or not a array!', 403);
            }

            //generate xml
            $exportLineItem = null;
            foreach ($exportLineItem_arr as $value) {
                $exportLineItem .=
                    "<ExportLineItem>\n".
                    "<LineNumber>".$value["LineNumber"]."</LineNumber>\n".
                    "<Quantity>".$value["Quantity"]."</Quantity>\n".
                    "<QuantityUnit>".$value["QuantityUnit"]."</QuantityUnit>\n".
                    "<Description>".$value["Description"]."</Description>\n".
                    "<Value>".$value["Value"]."</Value>\n".
                    "<IsDomestic>".$value["IsDomestic"]."</IsDomestic>\n".
                    "<CommodityCode>".$value["CommodityCode"]."</CommodityCode>\n".
                    "<ECCN>".$value["ECCN"]."</ECCN>\n".
                    "<Weight>\n".
                    "  <Weight>".$value["Weight"]['WeightUnit']."</Weight>\n".
                    "  <WeightUnit>".$value["Weight"]['WeightUnit']."</WeightUnit>\n".
                    "</Weight>\n".
                    "</ExportLineItem>\n";
            }
            //replace data
            return str_replace('<ExportDeclaration></ExportDeclaration>', $exportDeclaration.$exportLineItem, $data);
        }
        //delete ExportDeclaration node
        return str_replace('<ExportDeclaration></ExportDeclaration>', "\n", $data);
    }

    public function reference($data, $labelInfo)
    {
        //must be an array
        $reference_arr = $labelInfo['Reference'] ? : null;
        if (empty($reference_arr) || !is_array($reference_arr)) {
            response('Reference is null or not a array!', 403);
        }

        //generate xml
        $reference = null;
        foreach ($reference_arr as $reference_item) {
            $reference .=
                "<Reference>\n".
                "  <ReferenceID>".@$reference_item['ReferenceID']."</ReferenceID>\n".
                "  <ReferenceType>".@$reference_item['ReferenceType']."</ReferenceType>\n".
                "</Reference>\n";
        }

        //replace data
        return str_replace('<Reference></Reference>', $reference, $data);
    }

    public function pieces($data, $labelInfo)
    {
        //must be an array
        $pieces_arr = $labelInfo['ShipmentDetails']['Pieces'];
        $pieces_count = count($pieces_arr);
        if ($pieces_count == 0 || !is_array($pieces_arr)) {
            response('Pieces is null or not a array!', 403);
        } else if ($pieces_count > 99) {
            response('Pieces must less than 99!', 403);
        }

        //generate xml
        $pieces = null;
        foreach ($pieces_arr as $item) {
            $pieces .=
                "<Piece>\n".
                "	<PieceID>".$item['PieceID']."</PieceID>\n".
                "	<PackageType>".@$item['PackageType']."</PackageType>\n".
                "	<Weight>".@$item['Weight']."</Weight>\n".
                "	<DimWeight>".@$item['DimWeight']."</DimWeight>\n".
                "	<Width>".@$item['Width']."</Width>\n".
                "	<Height>".@$item['Height']."</Height>\n".
                "	<Depth>".@$item['Depth']."</Depth>\n".
                "	<PieceReference>\n".
                "		<ReferenceID>".$item['ReferenceID']."</ReferenceID>\n".
                "	</PieceReference>\n".
                "</Piece>\n";
        }

        //replace data
        return str_replace('<Piece></Piece>', $pieces, $data);
    }
}