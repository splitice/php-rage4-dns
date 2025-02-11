<?php
namespace Splitice\Rage4;

class Rage4Exception extends \Exception {
	public $apidata;

    public function __construct($message = "", $code = 0, Exception $previous = null, $apidata = null) {
        parent::__construct($message, $code, $previous);
        $this->apidata = $apidata;
    }

	public function getApidata(): mixed
	{
		return $this->apidata;
	}

}