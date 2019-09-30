<?php

namespace app\common\utils\payment\kernel\exceptions;

class InvalidArgumentException extends Exception
{
    /**
     * Raw error info.
     *
     * @var array|string
     */
    public $raw;

    /**
     * Bootstrap.
     *
     * @param string $message
     * @param array|string $raw
     * @param int|string $code
     */
    public function __construct($message, $raw = '', $code = 3)
    {
        parent::__construct($message, intval($code));

        $this->raw = $raw;
    }
}
