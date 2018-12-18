<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Apibeatlog;

/**
 */
class ApiBeatClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Apibeatlog\LogRequestData $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function LogInfo(\Apibeatlog\LogRequestData $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/apibeatlog.ApiBeat/LogInfo',
        $argument,
        ['\Apibeatlog\LogResponseData', 'decode'],
        $metadata, $options);
    }

}
