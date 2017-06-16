<?php

namespace DHL\Api;

class TrackingEvent
{
    use Tool;

    /*
     * get the client data,
     * @param $tracking_list
     * */
    public function get($tracking_list)
    {
        $api = new Api();
        $tracking_arr = $this->handle($tracking_list);

        try{
            $result = $api->tracking($tracking_arr);
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit();
        }

        return $result;
    }

    /*
     * handle response data, objet to array
     * @param $trackingXml, $tracking_list
     * @return array
     * */
    public function trackingInfo($trackingXml, $tracking_list)
    {
        $array = [];
        foreach ($trackingXml as $xml) {
            $object = simplexml_load_string($xml);
            $array[] = $this->object_to_array($object);
        }
        $result = $this->details($array, $tracking_list);

        return $result;
    }

    /*
     * handle respose data ,return data in the correct format
     * @param $trackingInfo, $tracking_list
     * */
    public function details($trackingInfo, $tracking_list)
    {
        $result = [];
        foreach ($trackingInfo as $item) {
            $info = $item['AWBInfo'];
            $tracking_number = $info['AWBNumber'];
            $order_number = $this->orderNumber($tracking_number, $tracking_list);
            $details_result = array();
            $latest_status = 'NOT_FOUND';
            $duration = 0;

            if (strtolower($info['Status']['ActionStatus']) == 'success') {

                $details_arr = $info['ShipmentInfo']['ShipmentEvent'];

                foreach ($details_arr as $key => $value) {
                    $date[] = $value['Date'];
                    $details_result[] = $value['Date'].' '.$value['Time'].'-'.$value['ServiceEvent']['Description'].'-'.$value['ServiceArea']['Description'];
                }

                $count = count($details_arr);
                $latest_status = $details_arr[$count-1]['ServiceEvent']['Description'];
                $duration = $this->duration($date[0], $date[$count - 1]);
            }

            $result[] = [
                'order_number' => $order_number,
                'tracking_number' => $tracking_number,
                'details' => $details_result,
                'latest_status' => $latest_status,
                'duration' => $duration,
            ];
        }

        return $result;
    }

    /*
     * Split array
     * @param $trackingInfo
     * */
    public function handle($tracking_info)
    {
        $tracking_arr = array();
        foreach ($tracking_info as $item) {
            $tracking_arr[] = $item;
        }
        return $tracking_arr;
    }

    /*
     * Calculate number of days interval
     * $param $start, $end
     * */
    public function duration($start, $end)
    {
        $start = strtotime($start);
        $end = strtotime($end);

        return (int)abs(($start - $end) / 86400);
    }

    /*
     * The order number via the tracking number
     * $param $tracking_number, $tracking_list
     * */
    public function orderNumber($tracking_number, $tracking_list)
    {
        foreach ($tracking_list as $item) {
            if ($item['tracking_number'] == $tracking_number) {
                return $item['order_number'];
            }
        }
    }
}