<?php
namespace Splitice\Rage4;

class Rage4Exception extends \Exception {
	/** @var mixed */
	public $apidata;

    /**
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     * @param mixed $apidata
     */
    public function __construct($message = "", $code = 0, \Exception $previous = null, $apidata = null) {
        parent::__construct($message, $code, $previous);
        $this->apidata = $apidata;
    }

	/**
	 * @return mixed
	 */
	public function getApidata()
	{
		return $this->apidata;
	}

}
