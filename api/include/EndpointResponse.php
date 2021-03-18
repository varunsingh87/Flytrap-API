<?php 

namespace Flytrap;

class EndpointResponse {
    protected $data;

    public function __construct($data = []) {
        $this->data = $data;
    }

    public function outputSuccessWithData() {
        return [
            "statusCode" => 200,
            "data" => $this->data
        ];
    }

    public function outputSuccessWithoutData() {
        return [
            "statusCode" => 201
        ];
    }

    public function outputGenericError($extraMsg = '') {
        return [
            "statusCode" => 500,
            "error" => [
                "message" => "A system error occurred$extraMsg"
            ]
        ];
    }
}

?>