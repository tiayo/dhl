<?php

namespace DHL\Api;

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

class Api
{
    protected $url;
    protected $option;
    protected $headers;
    protected $messageReference;
    protected $client;
    protected $trackingEvent;
    protected $result_xml;
    protected $config;

    public function __construct()
    {
        $this->url = config('dhl.DHLurl');
        $this->headers = ['Content-Type' => 'text/xml'];
        $this->messageReference = config('dhl.MessageReference');
        $this->trackingEvent = new TrackingEvent();

        //GuzzleHttp retry
        $handlerStack = HandlerStack::create( new CurlHandler() );
        $handlerStack->push( Middleware::retry( $this->retryDecider(), $this->retryDelay() ) );
        $this->client = new Client( array( 'handler' => $handlerStack ) );
    }

    private function retryDecider()
    {
        return function ($retries,Request $request,Response $response = null,RequestException $exception = null) {
            // Limit the number of retries to 5
            if ($retries >= 5 ) {
                return false;
            }

            // Retry connection exceptions
            if ($exception instanceof ConnectException) {
                return true;
            }

            if ($response) {
                // Retry on server errors
                if ($response ->getStatusCode() >= 500 ) {
                    return true;
                }
            }
            return false;
        };
    }

    private function retryDelay(){
        return function( $numberOfRetries ) {
            return 1000 * $numberOfRetries;
        };
    }

    /*
     * upload data and get back
     * @param $labelInfo
     * */
    public function label($labelInfo)
    {
        if (empty($labelInfo)) {
            throw new \Exception('label info is null', 403);
        }

        //get ShipmentRequest.xml
        $data = file_get_contents(dirname(__DIR__).'/xml/ShipmentRequest.xml');

        //handle xml data
        $handle = new DataHandle();
        $data = $handle->lable($data, $labelInfo);

        //send to api and return result
        $options = [
            'headers' => $this->headers,
            'body' => $data,
        ];

        $post = $this->client->request('POST', $this->url, $options)->getBody()->getContents();

        //validation adopt, retuan result
        try{
            $this->validate($post);
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit();
        }

        return simplexml_load_string($post);
    }

    /*
     * upload data and get back
     * @param $tracking_list
     *
     * */
    public function tracking($tracking_list)
    {
        if (empty($tracking_list)) {
            throw new \Exception('tracking number is null', 403);
        }
        $count = count($tracking_list);
        $options = null;

        //get TrackingRequest.xml
        $data = file_get_contents(dirname(__DIR__).'/xml/TrackingRequest.xml');
        $handle = new DataHandle();
        foreach ($tracking_list as $item) {
            $simple = $handle->tracking($data, $item['tracking_number']);
            //option
            $options[] = [
                'headers' => $this->headers,
                'body' => $simple->saveXML()
            ];
        }

        //send api
        $pool = new Pool($this->client, call_user_func($this->request($count, $options)), [
            'concurrency' => 10,
            'fulfilled' => function ($response, $index) {
                $this->result_xml[] = $response->getBody()->getContents();
            },
            'rejected' => function ($reason, $index) {

            },
        ]);

        $pool->promise()->wait();

        $result = $this->trackingEvent->trackingInfo($this->result_xml, $tracking_list);

        return $result;
    }

    public function labelRequest($info)
    {
        if (empty($info)) {
            throw new \Exception('label info is null', 403);
        }

        //get ShipmentRequest.xml
        $data = file_get_contents(dirname(__DIR__).'/xml/LaberRequest.xml');

        //handle xml data
        $handle = new LabelRequest();
        $data = $handle->handle($data, $info);

        //send to api and return result
        $options = [
            'headers' => $this->headers,
            'body' => $data,
        ];

        $post = $this->client->request('POST', $this->url, $options)->getBody()->getContents();

        //validation adopt, retuan result
        try{
            $this->validate($post);
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit();
        }

        return simplexml_load_string($post);
    }

    public function request($count, $options)
    {
        $client = $this->client;
        return $requests = function () use ($count ,$client, $options) {
            $uri = $this->url;
            for ($i = 0; $i < $count; $i++) {
                yield function() use ($client, $uri, $options, $i) {
                    return $client->postAsync($uri, $options[$i]);
                };
            }
        };
    }

    /*
     * Verify error
     * @param $post
     * @sucess return original data
     * @error response error message
     * */
    public function validate($post)
    {
        $post = simplexml_load_string($post);
        $response = $post->Response->Status;
        if (strtolower($response->ActionStatus) == 'error') {
            throw new \Exception($response->Condition->ConditionData, 403);
        }
        return $post;
    }
}