<?php
/**
 * Created by IntelliJ IDEA.
 * User: root
 * Date: 18-11-12
 * Time: 下午2:57
 */


namespace App\Common\Grpc;

class GrpcClient
{

    static $client;
    static $request;

    public static function greet($apiUri, $logData)
    {
        list($client, $request) = self::getInstance($apiUri);

        $request->setSource($logData['service']);
        $request->setMessage($logData['message']);
        $request->setData(json_encode($logData['data']));
        $request->setService($logData['service']);
        $request->setHost($logData['host']);

        list($reply, $res) = $client->LogInfo($request)->wait();

        return $res;
    }


    public static function getInstance($apiUri)
    {
        if (!self::$client) {
            self::$client = new \Apibeatlog\ApiBeatClient($apiUri,[
                'credentials' => \Grpc\ChannelCredentials::createInsecure(),
            ]);
        }

        if (!self::$request) {//实例化log请求
            self::$request = new \Apibeatlog\LogRequestData();
        }

        return [self::$client, self::$request];
    }

}